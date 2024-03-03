<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\CongeRepository;
use Carbon\Carbon;

class UtimePaiementService
{
    private CongeRepository $congeRepository;

    public function __construct(
        CongeRepository $congeRepository,
    )
    {
        $this->congeRepository = $congeRepository;
    }

    public function getJourCongesAcquis(Personal $personal): int|float|null
    {
        $lastCongesOfpersonal = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $today = Carbon::today();

        if ($lastCongesOfpersonal) {
            $dateDernierRetourCgs = $lastCongesOfpersonal->getDateDernierRetour();
            $workMonth = ($today->diff($dateDernierRetourCgs)->days) / 30;
        } else {
            $workMonth = ($today->diff($dateEmbauche)->days) / 30;
        }
        return $workMonth * 2.2;
    }
}