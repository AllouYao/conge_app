<?php

namespace App\Service;

use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\Settings\TauxHoraireRepository;
use App\Utils\Status;

class AbsenceService
{
    private AbsenceRepository $absenceRepository;
    private $hourRate;


    public function __construct(AbsenceRepository $absenceRepository,private TauxHoraireRepository $tauxHoraireRepository)
    {
        $this->absenceRepository = $absenceRepository;
        $tauxHorraire = $this->tauxHoraireRepository->findOneBy(['isActive'=>true]);
        $this->hourRate = $tauxHorraire->getAmount() ?? Status::TAUX_HEURE;
    }

    /**
     * @param Personal $personal
     * @param int $month
     * @param int $year
     * @return float
     */


    /** Retourne le nombre total d'heures non travailler en fonction du notre de jour d'absence */
    private function getHours(Absence $absence): float|int
    {
        $startedDate = $absence->getStartedDate();
        $endedDate = $absence->getEndedDate();
        $diff = $endedDate->diff($startedDate);
        $totalAbsenceDay = (int)$diff->format('%d');
        // 8 heure par jour de travail
        return $totalAbsenceDay * 8;
    }

    /** Retourne le montant que fait des abasence dans le moi */
    public function getAmountByMonth(Personal $personal, int $month, int $year): float
    {
        $salaireCategoriel = $personal->getSalary()->getBaseAmount();
        $hourRate = $this->hourRate;
        $salaireHorraire = $salaireCategoriel / $hourRate;
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);

        foreach ($absences as $absence) {
            $totalHours = $this->getHours($absence);
            $hourRate -= $totalHours; // TAUX_HORRAIRE - NBRE HEURE ABSENEC
        }

        return $hourRate * $salaireHorraire;
    }

    /** Retourne le solde catégoriel par absence */
    public function getAmountByAbsence(Absence $absence): float|int
    {
        $salaireCategoriel = $absence->getPersonal()->getSalary()->getBaseAmount();
        $hourRate = $this->hourRate;
        $salaireHorraire = $salaireCategoriel / $hourRate;

        if ($absence->isJustified()) {
            return $hourRate * $salaireHorraire;
        }

        $totalHours = $this->getHours($absence);
        $hourRate -= $totalHours; // TAUX_HORRAIRE - NBRE HEURE ABSENCE

        return $hourRate * $salaireHorraire;
    }


    /** Retourne le solde catégoriel par absence */
    public function getAmountDeduction(Absence $absence): float|int
    {
        $salaireCategoriel = $absence->getPersonal()->getSalary()->getBaseAmount();
        $hourRate = $this->hourRate;
        $salaireHorraire = $salaireCategoriel / $hourRate;
        if ($absence->isJustified()) {
            return 0;
        }
        $totalHours = $this->getHours($absence);
        return round($salaireHorraire * $totalHours, 2);
    }
}