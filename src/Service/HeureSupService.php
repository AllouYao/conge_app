<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Utils\Status;
use Carbon\Carbon;

class HeureSupService
{
    private HeureSupRepository $heureSupRepository;
    private PersonalRepository $personalRepository;

    public function __construct(HeureSupRepository $heureSupRepository, PersonalRepository $personalRepository)
    {
        $this->heureSupRepository = $heureSupRepository;
        $this->personalRepository = $personalRepository;
    }

    public function getAmountHeursSupp(Personal $personal): int|float
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $salaireBase = $personal->getCategorie()->getAmount();
        $tauxHoraire = ceil($salaireBase / Status::TAUX_HEURE);
        $heureSups = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
        $salaireHorraire = 0;

        foreach ($heureSups as $sup) {
            $JourNormalOrFerie = $sup->getTypeDay(); // normal/Férié/dimanche
            $startedHour = $sup->getStartedHour(); // heure debut
            $endedHour = $sup->getEndedHour(); // heure fin
            $jourOrNuit = $sup->getTypeJourOrNuit(); // Jour/nuit
            $diffHours = $startedHour->diff($endedHour);
            $totalHorraire = $diffHours->format('%h');
            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {
                // 15% jour normal ~ 115%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_JOUR_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6) {
                // 50% jour normal ~ 150%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_JOUR_OUVRABLE_EXTRA) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_NON_OUVRABLE) * $totalHorraire;
            }
        }
        return $salaireHorraire;
    }

    public function getAmountHeursSuppByID(int $perso): int|float
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $personals = $this->personalRepository->findBy(['id' => $perso]);
        foreach ($personals as $personal) {
            $salaireBase = $personal->getCategorie()->getAmount();
        }

        $heureSups = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
        $tauxHoraire = ceil($salaireBase / Status::TAUX_HEURE);
        $salaireHorraire = 0;

        foreach ($heureSups as $sup) {
            $JourNormalOrFerie = $sup->getTypeDay(); // normal/Férié/dimanche
            $startedHour = $sup->getStartedHour(); // heure debut
            $endedHour = $sup->getEndedHour(); // heure fin
            $jourOrNuit = $sup->getTypeJourOrNuit(); // Jour/nuit
            $diffHours = $startedHour->diff($endedHour);
            $totalHorraire = $diffHours->format('%h');
            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {
                // 15% jour normal ~ 115%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_JOUR_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6) {
                // 50% jour normal ~ 150%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_JOUR_OUVRABLE_EXTRA) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%
                $salaireHorraire = $salaireHorraire + ($tauxHoraire * Status::TAUX_NUIT_NON_OUVRABLE) * $totalHorraire;
            }
        }
        return $salaireHorraire;
    }

}