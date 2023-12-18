<?php

namespace App\Service;

use App\Entity\DossierPersonal\Conge;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use JetBrains\PhpStorm\NoReturn;

class CongeService
{
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;

    private CongeRepository $congeRepository;
    private PayrollRepository $payrollRepository;

    public function __construct(CongeRepository $congeRepository, PayrollRepository $payrollRepository)
    {
        $this->congeRepository = $congeRepository;
        $this->payrollRepository = $payrollRepository;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[NoReturn] public function calculate(Conge &$conge): void
    {
        $personal = $conge->getPersonal();
        $today = Carbon::now();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $anciennete = ceil(($dateEmbauche->diff($today)->y));
        $lastConge = $this->congeRepository->getLastConge($personal);
        $returnDate = $lastConge?->getDateDernierRetour();

        $genre = $personal->getGenre();
        $chargPeapleOfPersonal = $personal->getChargePeople();
        $suppConger = $this->suppConger($genre, $chargPeapleOfPersonal, $today);

        if ($genre === Status::FEMININ) {
            $moisTravailler = ceil((($dateEmbauche->diff($today)->days) + $this->echelonConge($anciennete) + $suppConger) / 30);
        } else {
            $moisTravailler = ceil((($dateEmbauche->diff($today)->days) + $this->echelonConge($anciennete)) / 30);
        }

        $moisAcquis = self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $moisTravailler;

        if ($returnDate) {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $returnDate, $today);
        } else {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $dateEmbauche, $today);
        }

        $salaireCategoriel = $personal->getCategorie()->getAmount();
        $gratification = ($salaireCategoriel * 75) / 100;
        $salaireMoyen = ceil(($salaireBrutPeriodique + $gratification) / $moisTravailler);
        $conge->setSalaireMoyen($salaireMoyen);
        $allocationConge = ceil(($salaireMoyen * $moisAcquis) / 30);
        $conge->setAllocationConge($allocationConge);
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


    public function getWeekdaysInYear($year): int
    {
        $weekdays = 0;
        $firstDay = Carbon::createFromDate($year, 1, 1);
        $lastDate = Carbon::createFromDate($year, 12, 31);
        for ($date = $firstDay; $date->lte($lastDate); $date->addDay()) {
            // Vérifier si le jour n'est pas un week-end (samedi ou dimanche)
            if ($date->isWeekday()) {
                $weekdays++;
            }
        }

        return $weekdays;
    }

}