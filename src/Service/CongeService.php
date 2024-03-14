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
    public string $messages;
    public string $success;


    public function __construct(
        CongeRepository      $congeRepository,
        PayrollRepository    $payrollRepository,
    )
    {
        $this->congeRepository = $congeRepository;
        $this->payrollRepository = $payrollRepository;
        $this->success = false;

    }


    /**
     * @throws Exception
     */
    public function congesPayerByFirst(Conge $conge): void
    {
        /** Information du salarié sujet au congés */
        $personal = $conge->getPersonal();
        $contract = $personal->getContract();
        $embauche = $contract->getDateEmbauche();
        $anciennete = $personal->getOlder(); // anciennete en années
        $genre = $personal->getGenre();
        $chargePeaple = $personal->getChargePeople();

        /** Information du congés */
        $dateDepartCgs = $conge->getDateDepart();
        $dateRetourCgs = $conge->getDateRetour();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $drCongeSupp1 = round($this->suppConger($genre, $chargePeaple, $dateDepartCgs), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $drCongeSupp2 = round($this->echelonConge($anciennete), 2);


        /** Determiner le salaire brut de la periode */
        $salreBrutPeriode = $this->payrollRepository->getPeriodiqueSalary1($personal, $dateDepartCgs);

        /** Determiner le nombre de mois travail depuis la date d'embauche jusqu'à la date de depart en congés */
        $worksMonths = round($this->getWorkMonth($embauche, $dateDepartCgs));


        /** Determiner nombre de jour ouvrable de congés */
        $dayOuvrableCgs = ceil($worksMonths * self::JOUR_CONGE_OUVRABLE);

        /** Determiner nombre de jour calandaire de congés */
        $dayCalandaireCgs = ceil($dayOuvrableCgs * self::JOUR_CONGE_CALANDAIRE);

        /** Determiner le salaire moyen mensuel (SMM) des 12 dernier mois travailler par le salarie */
        $smm = round($salreBrutPeriode / 12, 2);

        /** Determiner l'allocation de congé du salarié */
        $allocationCgs = round(($smm * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $worksMonths) / 30, 2);

        /** Dureé total de jour de congés à prendre */
        $totalDayCgs = $dayCalandaireCgs + $drCongeSupp1 + $drCongeSupp2;

        /** Durée total de congés pris par le salarié */
        $dayCgsExploiter = $dateRetourCgs->diff($dateDepartCgs)->days;

        /** Determiner le nombre de jours restant après expiration des jours de congés exploités */
        $remainingVacation = round($totalDayCgs - $dayCgsExploiter, 2);

        if ($worksMonths >= 12) {
            $conge
                ->setWorkMonths($worksMonths)
                ->setSalaireMoyen($smm)
                ->setDaysPlus($drCongeSupp1 + $drCongeSupp2)
                ->setTotalDays($totalDayCgs)
                ->setDays($dayCgsExploiter)
                ->setAllocationConge($allocationCgs)
                ->setRemainingVacation($remainingVacation);
            $this->success = true;

        } else {
            $this->messages = 'Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis la date de debut d\'exercice insufisant: '
                . ceil($worksMonths) . ' mois';
        }
    }

    /**
     * @throws Exception
     */
    public function congesPayerByLast(Conge $conge): void
    {
        /** Information du salarié sujet au congés */
        $personal = $conge->getPersonal();
        $contract = $personal->getContract();
        $embauche = $contract->getDateEmbauche();
        $anciennete = $personal->getOlder(); // anciennete en années
        $genre = $personal->getGenre();
        $chargePeaple = $personal->getChargePeople();

        /** Information du conges */
        $dateDepartCgs = $conge->getDateDepart();
        $dateRetourCgs = $conge->getDateRetour();
        $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $dateDernierRetourCgs = $lastConges?->getDateDernierRetour();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $drCongeSupp1 = round($this->suppConger($genre, $chargePeaple, $dateDepartCgs), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $drCongeSupp2 = round($this->echelonConge($anciennete), 2);

        /** Determiner le salaire brut de la periode */
        $salreBrutPeriode = $this->payrollRepository->getPeriodiqueSalary2($personal, $dateDernierRetourCgs);

        /** Determiner le nombre de mois travail depuis la date du dernier retour de congés jusqu'à la date de depart en congés actuel */
        $worksMonths = round($this->getWorkMonth($dateDernierRetourCgs, $dateDepartCgs));

        /** Determiner nombre de jour ouvrable de congés */
        $dayOuvrableCgs = ceil($worksMonths * self::JOUR_CONGE_OUVRABLE);

        /** Determiner nombre de jour calandaire de congés */
        $dayCalandaireCgs = ceil($dayOuvrableCgs * self::JOUR_CONGE_CALANDAIRE);

        /** Determiner le salaire moyen mensuel (SMM) des 12 dernier mois travailler par le salarie */
        $smm = round($salreBrutPeriode / 12, 2);

        /** Determiner l'allocation de congé du salarié */
        $allocationCgs = round(($smm * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $worksMonths) / 30, 2);

        /** Dureé total de jour de congés à prendre */
        $totalDayCgs = $dayCalandaireCgs + $drCongeSupp1 + $drCongeSupp2;

        /** Durée total de congés pris par le salarié */
        $dayCgsExploiter = $dateRetourCgs->diff($dateDepartCgs)->days;

        /** Determiner le nombre de jours restant après expiration des jours de congés exploités */
        $remainingVacation = round($totalDayCgs - $dayCgsExploiter, 2);

        if ($worksMonths >= 11) {
            $conge
                ->setWorkMonths($worksMonths)
                ->setSalaireMoyen($smm)
                ->setDaysPlus($drCongeSupp1 + $drCongeSupp2)
                ->setTotalDays($totalDayCgs)
                ->setDays($dayCgsExploiter)
                ->setAllocationConge($allocationCgs)
                ->setRemainingVacation($remainingVacation);
            $this->success = true;
        }else {
            $this->messages = 'Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis le retour de congés insufisant: '
                . ceil($worksMonths) . ' mois';
        }
    }


    /**
     * Conges supplémentaires 
     * @param mixed $genre
     * @param mixed $chargPeapleOfPersonal
     * @param mixed $today
     * @return int|float|null
     */
    public function suppConger(mixed $genre, mixed $chargPeapleOfPersonal, mixed $today): int|float|null

    {
        $nbJrCongeSupp = null;
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

    public function getWorkMonth(?DateTime $dateEmbauche, ?DateTime $dateDepart,): int|float
    {
        $workDays = $dateDepart->diff($dateEmbauche)->days;
        return $workDays / 30;
    }
}