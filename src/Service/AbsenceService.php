<?php

namespace App\Service;

use App\Utils\Status;
use App\Entity\Paiement\Campagne;
use App\Entity\DossierPersonal\Absence;
use App\Entity\DossierPersonal\Personal;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use App\Repository\DossierPersonal\AbsenceRepository;

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
    public function getAmountByMonth(Personal $personal, int $month, int $year): float
    {
        $salaireCategoriel = $personal->getSalary()->getBaseAmount();
        $salaireHorraire = $salaireCategoriel / Status::TAUX_HEURE;
        $amountMonth = 0;
        $workHours = Status::TAUX_HEURE;
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);
        
        foreach ($absences as $absence) {
            
            $totalHours = $this->getHours($absence, $salaireHorraire);
            $workHours -=  $totalHours; // TAUX_HORRAIRE - NBRE HEURE ABSENEC
        }
        
        $amountMonth = $workHours* $salaireHorraire;
        return $amountMonth;
    }

    private function getHours(Absence $absence)
    {
        $startedDate = $absence->getStartedDate();
        $endedDate = $absence->getEndedDate();
        $diff = $endedDate->diff($startedDate);
        $totalAbsenceDay = (int)$diff->format('%d');
        $totalHours = $totalAbsenceDay*8; // 8 heure par jour de travail
        
        return $totalHours;
    }
}