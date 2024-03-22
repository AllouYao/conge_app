<?php

namespace App\Service\Personal;

use App\Entity\DossierPersonal\Personal;
use App\Service\AbsenceService;
use Carbon\Carbon;

class ChargesServices
{

    public function __construct(
        private readonly AbsenceService $absenceService
    )
    {
    }

    /** Montant de la majoration des heure supplementaire hors periode de paie */
    public function amountMajorationHeureSupps(Personal $personal): float|int
    {
        $majoration = 0;
        $heureSupps = $personal->getHeureSups();
        foreach ($heureSupps as $supp) {
            $majoration += $supp?->getAmount();
        }
        return $majoration;
    }

    /** Montant de la prime d'anciènneté du personal hors periode de paie */
    public function amountAnciennete(Personal $personal): float|int
    {
        $salaireCategoriel = (int)$personal->getCategorie()->getAmount();
        $anciennete = (double)$personal->getOlder();

        if ($anciennete >= 2 && $anciennete < 3) {
            $primeAnciennete = $salaireCategoriel * 2 / 100;
        } elseif ($anciennete >= 3 && $anciennete <= 25) {
            $primeAnciennete = ($salaireCategoriel * $anciennete) / 100;
        } elseif ($anciennete >= 26) {
            $primeAnciennete = ($salaireCategoriel * 25) / 100;

        } else {
            $primeAnciennete = 0;
        }
        return $primeAnciennete;
    }

    /** Montant du congés payés hors periode de paie */
    // Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation conges du mois actuel.

    /** Montant du brut et du brut imposable provisoire et en fonction des heure d'absence hors periode de paie */
    public function amountSalaireBrutAndImposable(Personal $personal): array
    {
        $date = Carbon::today();
        $categorielWithAbsence = $this->absenceService->getAmountByMonth($personal, $date->month, $date->year);
        $actuelCategoriel = (int)$personal->getCategorie()->getAmount();
        if ($personal->getAbsences()->count() > 0) {
            $salaireCategoriel = $categorielWithAbsence;
            $salaireBrut = $personal->getSalary()->getBrutAmount() - $actuelCategoriel + $salaireCategoriel;
            $brutImposable = $personal->getSalary()->getBrutImposable() - $actuelCategoriel + $categorielWithAbsence;
        } else {
            $salaireCategoriel = $actuelCategoriel;
            $salaireBrut = $personal->getSalary()->getBrutAmount();
            $brutImposable = $personal->getSalary()->getBrutImposable();
        }
        return [
            'salaire_categoriel' => $salaireCategoriel,
            'brut_amount' => ceil($salaireBrut),
            'brut_imposable_amount' => ceil($brutImposable)
        ];
    }

    /** Nombre de part du personal hors periode de paie */
    public function nombrePartPersonal(Personal $personal): float|int
    {
        $nbrePart = [
            'CELIBATAIRE' => 1,
            'MARIE' => 2,
            'VEUF' => 1,
            'DIVORCE' => 1
        ];
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();

        //Ici je voudrais mouvementé le nbre de part du salarié en fonction du nombre d'enfant à charge
        if ($personal->getEtatCivil() === 'CELIBATAIRE' || $personal->getEtatCivil() === 'DIVORCE') {
            return match (true) {
                $chargePeople == 1 => 2,
                $chargePeople == 2 => 2.5,
                $chargePeople == 3 => 3,
                $chargePeople == 4 => 3.5,
                $chargePeople == 5 => 4,
                $chargePeople == 6 => 4.5,
                $chargePeople > 6 => 5,
                default => 1,
            };
        } elseif ($personal->getEtatCivil() === 'MARIE') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 2,
            };
        } elseif ($personal->getEtatCivil() === 'VEUF') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 1,
            };
        }
        return $nbrePart[$personal->getEtatCivil()];
    }


}