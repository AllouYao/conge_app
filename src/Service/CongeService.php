<?php

namespace App\Service;

use App\Entity\DossierPersonal\Conge;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class CongeService
{
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;

    private CongeRepository $congeRepository;
    private PayrollRepository $payrollRepository;
    private EtatService $etatService;

    public function __construct(
        CongeRepository   $congeRepository,
        PayrollRepository $payrollRepository,
        EtatService       $etatService
    )
    {
        $this->congeRepository = $congeRepository;
        $this->payrollRepository = $payrollRepository;
        $this->etatService = $etatService;
    }

    /**
     * @param Conge $conge
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function calculate(Conge &$conge): void
    {
        $personal = $conge->getPersonal();
        $today = Carbon::now();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $anciennete = ($today->diff($dateEmbauche)->y); // anciennete en années
        $lastConge = $this->congeRepository->getLastConge($personal);
        $returnDate = $lastConge?->getDateDernierRetour(); // date retour du dernier congé


        $genre = $personal->getGenre();
        $chargPeapleOfPersonal = $personal->getChargePeople();
        $suppConger = $this->suppConger($genre, $chargPeapleOfPersonal, $today);

        if ($genre === Status::FEMININ) {
            $moisTravailler = ceil((($today->diff($dateEmbauche)->days) + $this->echelonConge($anciennete) + $suppConger) / 30);
        } else {
            $moisTravailler = (($today->diff($dateEmbauche)->days) + $this->echelonConge($anciennete)) / 30;
        }

        $gratification = $this->etatService->getGratifications($dateEmbauche, $today, $personal->getCategorie()->getAmount());
        $annee = $today->year;
        $premierJour = new Carbon("$annee-01-01");
        $dernierJour = new Carbon("$annee-12-31");

        $conge->getPersonal()->getSalary()->setGratification($gratification);

        if ($returnDate) {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $returnDate, $today);
        } else {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $dateEmbauche, $today);
        }


        $salaireMoyen = ($salaireBrutPeriodique * $moisTravailler) / $moisTravailler;
        $conge->setSalaireMoyen((int)$salaireMoyen);
        $allocationConge = ($salaireMoyen * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $moisTravailler) / 30;
        $conge->setAllocationConge((int)$allocationConge)
            ->getPersonal()->getSalary()
            ->setCongePayer($allocationConge);

    }

    public function suppConger(mixed $genre, mixed $chargPeapleOfPersonal, mixed $today): int|float
    {
        $nbJrCongeSupp = 0;
        if ($genre === Status::FEMININ) {
            foreach ($chargPeapleOfPersonal->getValues() as $item) {
                $yearOfChargPeaple = $item->getBirthday()->diff($today)->y;
                if ($yearOfChargPeaple < 21) {
                    $nbJrCongeSupp += 2;
                } elseif ($chargPeapleOfPersonal->count() >= 4 && $yearOfChargPeaple > 21) {
                    $nbJrCongeSupp += 2;
                }
            }
        }
        return $nbJrCongeSupp;
    }

    public function echelonConge(mixed $anciennete): int
    {
        return match ($anciennete) {
            $anciennete >= 5 && $anciennete < 10 => 1,
            $anciennete >= 10 && $anciennete < 15 => 2,
            $anciennete >= 15 && $anciennete < 20 => 3,
            $anciennete >= 20 && $anciennete < 25 => 5,
            $anciennete > 25 => 7,
            default => 0,
        };
    }
}