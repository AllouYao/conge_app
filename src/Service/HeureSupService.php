<?php

namespace App\Service;

use App\Entity\DossierPersonal\HeureSup;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class HeureSupService
{
    private EntityManagerInterface $manager;
    private HeureSupRepository $heureSupRepository;



    public function __construct(EntityManagerInterface $entityManager, HeureSupRepository $heureSupRepository)
    {
        $this->manager = $entityManager;
        $this->heureSupRepository = $heureSupRepository;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     * @throws NonUniqueResultException
     */
    public function getAmountByMonth(Personal $personal, int $month, int $year): float
    {

        $montantTotal = 0;

        $heureSups = $this->heureSupRepository->getHeureSupByDate($personal, $month, $year);

        foreach ($heureSups as $heureSup) {

            $montant = $this->calculHeureSup($heureSup);
            $montantTotal += $montant;
        }

        return $montantTotal;
    }
    private function calculHeureSup(HeureSup $heureSup)
    {

        $JourNormalOrFerie = $heureSup->getTypeDay(); // normal/Férié/dimanche
        
        $startedHour = $heureSup->getStartedHour();
        $endedHour = $heureSup->getEndedHour();
        $jourOrNuit = $heureSup->getTypeJourOrNuit(); // Jour/nuit

        $diff = $startedHour->diff($endedHour);
        $totalHorraire = $diff->format('%h');

        if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {

            // 15% jour normal 
            $salaireHorraire = Status::SALAIRE_HORRAIRE_CATEGORIEL * Status::TAUX_JOUR_OUVRABLE * $totalHorraire;
            return $salaireHorraire;
        }

        if($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6){

            // 50% jour normal 
            
            $salaireHorraire = Status::SALAIRE_HORRAIRE_CATEGORIEL*Status::TAUX_JOUR_OUVRABLE_EXTRA* $totalHorraire;
            return $salaireHorraire;
            
        }

        if (($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT)|| ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR)) {

            $salaireHorraire = Status::SALAIRE_HORRAIRE_CATEGORIEL * Status::TAUX_JOUR_OUVRABLE_EXTRA * $totalHorraire;
            return $salaireHorraire;
            
        }

        if ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {

            $salaireHorraire = Status::SALAIRE_HORRAIRE_CATEGORIEL * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE * $totalHorraire;
            return $salaireHorraire;
            
        }

        if ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {

            $salaireHorraire = Status::SALAIRE_HORRAIRE_CATEGORIEL * Status::TAUX_NUIT_NON_OUVRABLE * $totalHorraire;
            return $salaireHorraire;
            
        }
    }
}