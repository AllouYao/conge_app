<?php

namespace App\Service;

use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class AbsenceService
{
    private $absenceRepository;



    public function __construct(AbsenceRepository $absenceRepository)
    {
        $this->absenceRepository = $absenceRepository;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     * @throws NonUniqueResultException
     */
    public function getAmountByMonth(Personal $personal, int $month, int $year, $salaireHorraire): float
    {
        $totalAbsenceAmount = 0;

        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);
        dd($absences);


        foreach ($absences as $absence) {

            $amount = $this->calculAbsenceTime($absence, $salaireHorraire);

            $totalAbsenceAmount += $amount;
        }
        return $totalAbsenceAmount;
    }

    private function calculAbsenceTime(Absence $absence, $salaireHorraire)
    {
        $startedDate = $absence->getStartedDate();
        $endedDate = $absence->getEndedDate();
        $diff = $endedDate->diff($startedDate);
        $totalAbsenceDay = $diff->format('%d');
        $absenceAmount = $totalAbsenceDay * $salaireHorraire;

        return $absenceAmount;
    }
}