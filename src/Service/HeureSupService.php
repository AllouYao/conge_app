<?php

namespace App\Service;

use App\Entity\DossierPersonal\HeureSup;
use App\Entity\DossierPersonal\Personal;
use App\Repository\DevPaie\WorkTimeRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\TauxHoraireRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HeureSupService
{
    private HeureSupRepository $heureSupRepository;
    private PersonalRepository $personalRepository;
    private TauxHoraireRepository $horaireRepository;
    private EntityManagerInterface $manager;
    private  $tokenStorage;
    private $defaultRate = 100;




    public function __construct(
        HeureSupRepository     $heureSupRepository,
        PersonalRepository     $personalRepository,
        TauxHoraireRepository  $horaireRepository,
        EntityManagerInterface $manager,
        TokenStorageInterface $tokenStorage,
        private WorkTimeRepository $workTimeRepository
        
    )
    {
        $this->heureSupRepository = $heureSupRepository;
        $this->personalRepository = $personalRepository;
        $this->horaireRepository = $horaireRepository;
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;

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
            $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_15_PERCENT']);

            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= $workTime->getHourValue()?? 6) {
                // 15% jour normal ~ 115%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 115/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > $workTime->getHourValue()?? 6) {
                // 50% jour normal ~ 150%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_50_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 150/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_75_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 175/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_75_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 175/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%

                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_100_PERCENT']);
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 200/100) * $totalHorraire;
            }
        }
        return $amountHeureSup;
    }

    public function heureSupp(array $data, Personal $personal): void
    {
        
        $heureSupps = $data['heureSup'];
        /** @var HeureSup $heureSupps */
        foreach ($heureSupps as $heureSupp) { 
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

            $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_15_PERCENT']);

            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= $workTime->getHourValue()?? 6) {
                // 15% jour normal ~ 115%
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 115/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > $workTime->getHourValue()?? 6) {
                // 50% jour normal ~ 150%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_50_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 150/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_75_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 175/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 175%
                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_75_PERCENT']);

                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 175/100) * $totalHorraire;
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 75% jour ferié or dimanche nuit ~ 200%

                $workTime = $this->workTimeRepository->findOneBy(['type' => 'MAJORATION_100_PERCENT']);
                $amountHeureSup = $amountHeureSup + ($salaireHoraire * ($workTime->getRateValue()+$this->defaultRate)/100 ?? 200/100) * $totalHorraire;
            }

            $heureSupp
                ->setStatus(Status::EN_ATTENTE) 
                ->setPersonal($personal)
                ->setAmount((int)$amountHeureSup)
                ->setUser($this->tokenStorage->getToken()->getUser());
            $this->manager->persist($heureSupp);
        }

    }

}