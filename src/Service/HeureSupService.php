<?php

namespace App\Service;

use App\Entity\DossierPersonal\HeureSup;
use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\TauxHoraireRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class HeureSupService
{
    private HeureSupRepository $heureSupRepository;
    private PersonalRepository $personalRepository;
    private TauxHoraireRepository $horaireRepository;
    private EntityManagerInterface $manager;

    public function __construct(
        HeureSupRepository     $heureSupRepository,
        PersonalRepository     $personalRepository,
        TauxHoraireRepository  $horaireRepository,
        EntityManagerInterface $manager
    )
    {
        $this->heureSupRepository = $heureSupRepository;
        $this->personalRepository = $personalRepository;
        $this->horaireRepository = $horaireRepository;
        $this->manager = $manager;
    }

    public function getAmountHeursSupp(Personal $personal): int|float
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $heureSups = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
        $salaireHorraire = 0;

        foreach ($heureSups as $sup) {
            $salaireHorraire = $salaireHorraire + $sup->getAmount();
        }
        return $salaireHorraire;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getAmountHeursSuppByID(int $perso): int|float
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $personal = null;
        $salaireBase = 0;
        $personals = $this->personalRepository->findBy(['id' => $perso]);
        foreach ($personals as $personal) {
            $salaireBase = (int)$personal->getCategorie()->getAmount();
        }

        $tauxHoraire = $this->horaireRepository->active();
        
        $heureSups = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
        $salaireHoraire = $salaireBase / (double)$tauxHoraire?->getAmount() ;
        $amountHeureSup = 0;

        foreach ($heureSups as $sup) {

            $JourNormalOrFerie = $sup->getTypeDay(); // normal/Férié/dimanche
            $startedHour = $sup->getStartedHour(); // heure debut
            $endedHour = $sup->getEndedHour(); // heure fin
            $jourOrNuit = $sup->getTypeJourOrNuit(); // Jour/nuit
            $diffHours = $startedHour->diff($endedHour);
            $totalHorraire = $diffHours->format('%h');
            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {
                // 15% jour normal ~ 115%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6) {
                // 50% jour normal ~ 150%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE_EXTRA) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_NON_OUVRABLE) * $totalHorraire;
            }
        }
        return $amountHeureSup;
    }

    public function heureSupp(array $data): void
    {
        $heureSupps = $data['heureSup'];
        /** @var HeureSup $heureSupp */
        foreach ($heureSupps as $index => $heureSupp) {
            /** @var Personal $personal */
            $personal = $heureSupp->getPersonal();
            $tauxHoraire = (double)$heureSupp->getTauxHoraire();
            $salaireBase = (int)$personal->getCategorie()->getAmount();
            $salaireHoraire = $salaireBase / $tauxHoraire;

            // Heure de debut
            $StartedfullDate = $heureSupp->getStartedDate();
            $StartedfullTime = $heureSupp->getStartedHour();
            $startedDate = $StartedfullDate->format('Y-m-d');
            $startedHour = $StartedfullTime->format('H:i:s');
            $fullNewDateTime = $startedDate . ' ' . $startedHour;
            $newFullDate = new Carbon($fullNewDateTime);
            $heureSupp->setStartedHour($newFullDate);

            // Date de fin
            $endedfullDate = $heureSupp->getEndedDate();
            $endedfullTime = $heureSupp->getEndedHour();
            $endedDate = $endedfullDate->format('Y-m-d');
            $endedHour = $endedfullTime->format('H:i:s');
            $fullNewDateTime = $endedDate . ' ' . $endedHour;
            $newFullDate = new Carbon($fullNewDateTime);
            $heureSupp->setEndedHour($newFullDate);

            $JourNormalOrFerie = $heureSupp->getTypeDay(); // normal/Férié/dimanche
            $jourOrNuit = $heureSupp->getTypeJourOrNuit(); // Jour/nuit
            $totalHorraire = (int)$heureSupp->getTotalHorraire();
            $amountHeureSup = 0;

            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {
                // 15% jour normal ~ 115%
                $amountHeureSup =  ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6) {
                // 50% jour normal ~ 150%
                $amountHeureSup = ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE_EXTRA) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $amountHeureSup = ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $amountHeureSup = ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%
                    $amountHeureSup = ($salaireHoraire * Status::TAUX_NUIT_NON_OUVRABLE) * $totalHorraire;
            }
            $heureSupp->setPersonal($personal)->setAmount((int)$amountHeureSup);
            $this->manager->persist($heureSupp);
        }

    }

}