<?php

namespace App\Service;

use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Utils\Status;

class AbsenceService
{
    private AbsenceRepository $absenceRepository;


    public function __construct(AbsenceRepository $absenceRepository)
    {
        $this->absenceRepository = $absenceRepository;
    }

    /**
     * @param Personal $personal
     * @param int $month
     * @param int $year
     * @return float
     */
    public function getAmountByMonth(Personal $personal, int $month, int $year): float
    {
        $salaireCategoriel = $personal->getSalary()->getBaseAmount();
        $workHours = Status::TAUX_HEURE;
        $salaireHorraire = $salaireCategoriel / $workHours;
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);

        foreach ($absences as $absence) {
            $totalHours = $this->getHours($absence);
            $workHours -= $totalHours; // TAUX_HORRAIRE - NBRE HEURE ABSENEC
        }

        return $workHours * $salaireHorraire;
    }

    private function getHours(Absence $absence): float|int
    {
        $startedDate = $absence->getStartedDate();
        $endedDate = $absence->getEndedDate();
        $diff = $endedDate->diff($startedDate);
        $totalAbsenceDay = (int)$diff->format('%d');

        // 8 heure par jour de travail
        return $totalAbsenceDay * 8;
    }
}