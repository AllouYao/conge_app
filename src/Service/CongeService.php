<?php

namespace App\Service;

use App\Entity\DossierPersonal\Conge;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\OldCongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Status;
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
    private OldCongeRepository $oldCongeRepository;


    public function __construct(
        CongeRepository   $congeRepository,
        PayrollRepository $payrollRepository, OldCongeRepository $oldCongeRepository,
    )
    {
        $this->congeRepository = $congeRepository;
        $this->payrollRepository = $payrollRepository;
        $this->success = false;

        $this->oldCongeRepository = $oldCongeRepository;
    }


    /**
     * @throws Exception
     */
    public function congesPayeFirst(Conge $conge): void
    {
        /** Information du salarié sujet au congés */
        $personal = $conge->getPersonal();
        $contract = $personal->getContract();
        $embauche = $contract->getDateEmbauche();
        $anciennete = $personal->getOlder(); // anciennete en années
        $genre = $personal->getGenre();
        $charge_peaple = $personal->getChargePeople();

        /** Information du congés */
        $date_depart_cgs = $conge->getDateDepart();
        $date_retour_cgs = $conge->getDateRetour();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $dr_conge_supp_1 = round($this->suppConger($genre, $charge_peaple, $date_depart_cgs), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $dr_conge_supp_2 = round($this->echelonConge($anciennete), 2);

        /** Determiner le nombre de mois travail depuis la date d'embauche jusqu'à la date de depart en congés */
        $works_months = round($this->getWorkMonth($embauche, $date_depart_cgs));

        /** Determiner nombre de jour ouvrable de congés */
        $day_ouvrable_cgs = ceil($works_months * self::JOUR_CONGE_OUVRABLE);

        /** Determiner nombre de jour calandaire de congés */
        $day_calandre_cgs = ceil($day_ouvrable_cgs * self::JOUR_CONGE_CALANDAIRE);

        /** Determiner le salaire moyen mensuel (SMM) des 12 dernier mois travailler par le salarie */
        if ($conge->getPersonal()->getPayrolls()->count() >= 12) {
            /** Determiner le salaire brut de la periode */
            $sal_brut_periode = $this->payrollRepository->getPeriodiqueSalary1($personal, $date_depart_cgs);
            $somme = round($sal_brut_periode / 12, 2);
        } else {
            $somme = $conge->getSalaireMoyen();
        }

        /** Determiner l'allocation de congé du salarié */
        $allocation_cgs = round(($somme * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $works_months) / 30, 2);

        /** Dureé total de jour de congés à prendre */
        $total_day_cgs = $day_calandre_cgs + $dr_conge_supp_1 + $dr_conge_supp_2;

        /** Durée total de congés pris par le salarié */
        $day_cgs_exploied = $date_retour_cgs?->diff($date_depart_cgs)->days;

        /** Determiner le nombre de jours restant après expiration des jours de congés exploités */
        $remaining_vacat = round($total_day_cgs - $day_cgs_exploied, 2);

        if ($works_months >= 12) {
            $conge
                ->setWorkMonths($works_months)
                ->setDaysPlus($dr_conge_supp_1 + $dr_conge_supp_2)
                ->setTotalDays($total_day_cgs)
                ->setDays($day_cgs_exploied)
                ->setAllocationConge($allocation_cgs)
                ->setRemainingVacation($remaining_vacat);
            $this->success = true;

        } else {
            $this->messages = 'Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis la date de debut d\'exercice insufisant: '
                . ceil($works_months) . ' mois';
        }
    }

    /**
     * @throws Exception
     */
    public function congesPayeByLast(Conge $conge): void
    {
        /** Information du salarié sujet au congés */
        $personal = $conge->getPersonal();
        $anciennete = $personal->getOlder(); // anciennete en années
        $genre = $personal->getGenre();
        $charge_peaple = $personal->getChargePeople();

        /** Information du conges */
        $date_depart_cgs = $conge->getDateDepart();
        $date_retour_cgs = $conge->getDateRetour();
        $last_conges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $historique_conge = $this->oldCongeRepository->findOneByPerso($personal->getId());
        $date_last_conges = $last_conges ? $last_conges->getDateDernierRetour() : $historique_conge->getDateRetour();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $dr_conge_supp_1 = round($this->suppConger($genre, $charge_peaple, $date_depart_cgs), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $dr_conge_supp_2 = round($this->echelonConge($anciennete), 2);

        /** Determiner le salaire brut de la periode */
        if ($last_conges) {
            $sal_brut_periode = round((int)$this->payrollRepository->getPeriodiqueSalary2($personal, $date_last_conges) / 12);
        } else {
            $sal_brut_periode = (int)$historique_conge->getSalaryAverage();
        }

        /** Determiner le nombre de mois travail depuis la date du dernier retour de congés jusqu'à la date de depart en congés actuel */
        $works_months = round($this->getWorkMonth($date_last_conges, $date_depart_cgs));

        /** Determiner nombre de jour ouvrable de congés */
        $day_ouvrable_cgs = ceil($works_months * self::JOUR_CONGE_OUVRABLE);

        /** Determiner nombre de jour calandaire de congés */
        $day_calandre_cgs = ceil($day_ouvrable_cgs * self::JOUR_CONGE_CALANDAIRE);

        /** Determiner le salaire moyen mensuel (SMM) des 12 dernier mois travailler par le salarie */
        $somme = round($sal_brut_periode, 2);

        /** Determiner l'allocation de congé du salarié */
        $allocation_cgs = round(($somme * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $works_months) / 30, 2);

        /** Dureé total de jour de congés à prendre */
        $total_day_cgs = $day_calandre_cgs + $dr_conge_supp_1 + $dr_conge_supp_2;

        /** Durée total de congés pris par le salarié */
        $day_cgs_exploied = $date_retour_cgs?->diff($date_depart_cgs)->days;

        /** Determiner le nombre de jours restant après expiration des jours de congés exploités */
        $remaining_vacat = round($total_day_cgs - $day_cgs_exploied, 2);

        if ($works_months >= 11) {
            $conge
                ->setWorkMonths($works_months)
                ->setSalaireMoyen($somme)
                ->setDaysPlus($dr_conge_supp_1 + $dr_conge_supp_2)
                ->setTotalDays($total_day_cgs)
                ->setDays($day_cgs_exploied)
                ->setAllocationConge($allocation_cgs)
                ->setRemainingVacation($remaining_vacat);
            $this->success = true;
        } else {
            $this->messages = 'Mr/Mdm ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                 n\'est pas éligible pour une acquisition de congés, nombre de mois travailler depuis le retour de congés insufisant: '
                . ceil($works_months) . ' mois';
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