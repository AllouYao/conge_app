<?php

namespace App\Service;

use App\Entity\DossierPersonal\Conge;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Exception;

class CongeService
{
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;

    private CongeRepository $congeRepository;
    private PayrollRepository $payrollRepository;
    private UtimePaiementService $utimePaiementService;
    private PrimesRepository $primesRepository;

    public function __construct(
        CongeRepository      $congeRepository,
        PayrollRepository    $payrollRepository,
        UtimePaiementService $utimePaiementService,
        PrimesRepository     $primesRepository
    )
    {
        $this->congeRepository = $congeRepository;
        $this->payrollRepository = $payrollRepository;
        $this->utimePaiementService = $utimePaiementService;
        $this->primesRepository = $primesRepository;
    }

    /**
     * @param Conge $conge
     * @throws Exception|Exception
     */
    public function calculate(Conge $conge): void
    {
        $personal = $conge->getPersonal();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $lastConge = $this->congeRepository->getLastCongeByID($personal->getId(), false); // Dernier conger pris
        $lastDateReturn = $lastConge?->getDateDernierRetour(); // Data de retour de congés
        $dayConge = (new Carbon($conge->getDateRetour()))->diff($conge->getDateDepart())->days; // Jour de congés pris par le salarié

        /** Process de determination des jour supplémentaire de congés  */
        $anciennete = $personal->getOlder(); // anciennete en années
        $startDate = $conge->getDateDepart();
        $genre = $personal->getGenre();
        $chargePeaple = $personal->getChargePeople();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $drCongeSupp1 = round($this->suppConger($genre, $chargePeaple, $startDate), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $drCongeSupp2 = round($this->echelonConge($anciennete), 2);


        /** Salaire brut de la période */
        if ($lastConge) {
            /** Determination de la période de reference ou le nombre de mois de travail */
            $periodeReference = round($this->getWorkMonth($lastDateReturn, $startDate), 2);
            if ($periodeReference >= 11) {
                /** Determination de la gratification */
                $basePeriode = round($this->utimePaiementService->getAmountSalaireBrutAndImposable($personal)['salaire_categoriel'], 2);
                $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
                if ($periodeReference < 12) {
                    $gratification = round(($basePeriode * $tauxGratif * ($periodeReference * 30) / 360), 2);
                } else {
                    $gratification = round(($basePeriode * $tauxGratif), 2);
                }
                /** Determiner la duree du congé en jour calandaire*/
                $drConge = ceil($periodeReference * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE);
                /** Avec 11 mois */
                $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary1($personal, $lastDateReturn), 2);
                /** Determiner le salaire moyen */
                $salaireMoyen = round((($brutPeriode + $gratification) / 11), 2);
                /** indemnite partiel de congés */
                $indemniteConge = round(($salaireMoyen / 30) * $drConge, 2);
                /** Determiner indemnite de conger pour chaque jours supplementaire  */
                $indemniteCongeSupp = round(($indemniteConge / ceil($periodeReference * self::JOUR_CONGE_OUVRABLE)) * ($drCongeSupp1 + $drCongeSupp2), 2);
                /** allocation de conge du salarié */
                $allocationConge = round($indemniteConge + $indemniteCongeSupp, 2);
                /** s'il décide de prendre un certains nombre de jours alors nous determinons le nombre de jours restant */
                $lastRemaining = $lastConge->getRemainingVacation();
                $remainingVacation = round($lastRemaining - $dayConge, 2);
            } else {
                throw new \Exception('Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis le retour de congés insufisant '
                    . ceil($periodeReference) . ' mois');
            }
        } else {
            /** Determination de la période de reference ou le nombre de mois de travail */
            $periodeReference = round($this->getWorkMonths($dateEmbauche, $startDate, $genre, $drCongeSupp2, $drCongeSupp1), 2);
            if ($periodeReference >= 12) {
                /** Determination de la gratification */
                $basePeriode = round($this->utimePaiementService->getAmountSalaireBrutAndImposable($personal)['salaire_categoriel'], 2);
                $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
                $gratification = round(($basePeriode * $tauxGratif), 2);
                /** Determiner la duree du congé en jour calandaire*/
                $drConge = ceil($periodeReference * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE);
                /** avec 12 mois */
                $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary2($personal, $startDate), 2);
                /** Determiner le salaire moyen */
                $salaireMoyen = round((($brutPeriode + $gratification) / 12), 2);
                /** indemnite partiel de congés */
                $indemniteConge = round(($salaireMoyen / 30) * $drConge, 2);
                /** Determiner indemnite de conger pour chaque jours supplementaire  */
                $indemniteCongeSupp = round(($indemniteConge / ceil($periodeReference * self::JOUR_CONGE_OUVRABLE)) * ($drCongeSupp1 + $drCongeSupp2), 2);
                /** allocation de conge du salarié */
                $allocationConge = round($indemniteConge + $indemniteCongeSupp, 2);
                /** s'il décide de prendre un certains nombre de jours alors nous determinons le nombre de jours restant */
                $remainingVacation = round($drConge - $dayConge, 2);
            } else {
                throw new \Exception('Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis la date de debut d\'exercice insufisant '
                    . ceil($periodeReference) . ' mois');
            }

        }
        $conge
            ->setAllocationConge($allocationConge)
            ->setGratification($gratification)
            ->setSalaireMoyen($salaireMoyen)
            ->setWorkMonths($periodeReference)
            ->setSalaryDue(round($brutPeriode / 12, 2))
            ->setDaysPlus($drCongeSupp1)
            ->setTotalDays($drConge)
            ->setDays($dayConge)
            ->setOlderDays($drCongeSupp2)
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
        switch ($anciennete) {
            case  $anciennete >= 5 && $anciennete < 10:
                $echelon = 1;
                break;
            case $anciennete >= 10 && $anciennete < 15:
                $echelon = 2;
                break;
            case $anciennete >= 15 && $anciennete < 20:
                $echelon = 3;
                break;
            case $anciennete >= 20 && $anciennete < 25:
                $echelon = 5;
                break;
            case $anciennete > 25:
                $echelon = 7;
                break;
            default:
                $echelon = 0;
                break;
        }
        return $echelon;
    }

    public function getWorkMonths(
        ?DateTime $dateEmbauche,
        ?DateTime $dateDepart,
        string    $genre,
        mixed     $echelonConge,
        mixed     $suppConger
    ): int|float
    {
        $workDays = $dateDepart->diff($dateEmbauche)->days;
        $workDays = $workDays + $echelonConge;
        if ($genre === Status::FEMININ) {
            $workDays = ($workDays + $suppConger);
        }
        return $workDays / 30;
    }

    public function getWorkMonth(?DateTime $dateEmbauche, ?DateTime $dateDepart,): int|float
    {
        $workDays = $dateDepart->diff($dateEmbauche)->days;
        return $workDays / 30;
    }
}