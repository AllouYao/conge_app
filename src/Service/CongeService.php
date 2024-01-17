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
    public function calculate(Conge $conge): void
    {
        $personal = $conge->getPersonal();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $startDate = $conge->getDateDepart();
        $anciennete = $personal->getOlder(); // anciennete en années
        $lastConge = $this->congeRepository->getLastCongeByID($personal->getId());
        $lastDateReturn = !$lastConge ? $conge->getDateRetour() : $lastConge->getDateDernierRetour();
        $dayConge = (new Carbon($conge->getDateRetour()))->diff($conge->getDateDepart())->days;
        $genre = $personal->getGenre();
        $chargPeapleOfPersonal = $personal->getChargePeople();
        $suppConger = $this->suppConger($genre, $chargPeapleOfPersonal, $startDate);
        $echelonConge = $this->echelonConge($anciennete);
        $olderDate = (new Carbon($startDate))->diff($dateEmbauche);
        $gratification = $this->etatService->getGratifications($olderDate, $personal->getCategorie()->getAmount());
        $moisTravailler = $this->getWorkMonths($dateEmbauche, $startDate, $genre, $echelonConge, $suppConger);
        $totalDays = self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $moisTravailler + $echelonConge + $suppConger;
        if ($lastConge) {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $lastDateReturn, $startDate);
        } else {
            $salaireBrutPeriodique = $this->payrollRepository->getTotalSalarie($personal, $dateEmbauche, $startDate);
        }
        $salaireMoyen = (($salaireBrutPeriodique + $gratification)) / $moisTravailler;
        $allocationConge = ($salaireMoyen * $totalDays) / 30;
        $remainingVacation = $totalDays - $dayConge;
        $conge
            ->setAllocationConge($allocationConge)
            ->setGratification($gratification)
            ->setDateDernierRetour($lastDateReturn)
            ->setSalaireMoyen((int)$salaireMoyen)
            ->setWorkMonths($moisTravailler)
            ->setSalaryDue($personal->getSalary()->getBrutAmount())
            ->setDaysPlus($suppConger)
            ->setTotalDays($totalDays)
            ->setDays($dayConge)
            ->setOlderDays($echelonConge)
            ->setRemainingVacation($remainingVacation);
    }

    /**
     * Conges supplémentaires
     * @param mixed $genre
     * @param mixed $chargPeapleOfPersonal
     * @param mixed $today
     * @return int|float
     */
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

    public function getWorkMonths(
        mixed  $dateEmbauche,
        mixed  $dateDepart,
        string $genre,
        mixed  $echelonConge,
        mixed  $suppConger
    ): int|float
    {
        $workDays = $dateDepart->diff($dateEmbauche)->days;
        $workDays = $workDays + $echelonConge;
        if ($genre === Status::FEMININ) {
            $workDays = ($workDays + $suppConger);
        }
        return $workDays / 30;
    }
}