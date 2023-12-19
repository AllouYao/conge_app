<?php

namespace App\Service;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use Carbon\Carbon;

class EtatService
{
    private PrimesRepository $primesRepository;
    private PersonalRepository $personalRepository;

    public function __construct(PrimesRepository $primesRepository, PersonalRepository $personalRepository)
    {
        $this->primesRepository = $primesRepository;
        $this->personalRepository = $personalRepository;
    }

    public function getPrimeAnciennete(int $personal): float|int
    {
        $today = Carbon::now();
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $salaireCategoriel = $personal->getCategorie()->getAmount();
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
        }
        $anciennete = ceil(($dateEmbauche->diff($today)->y));
        $primeAnciennete = 0;
        switch ($anciennete) {
            case $anciennete == 2:
                $primeAnciennete = ceil(($salaireCategoriel * 2) / 100);
                break;
            case $anciennete >= 3 && $anciennete <= 25:
                $primeAnciennete = ceil(($salaireCategoriel * $anciennete) / 100);
                break;
            case $anciennete >= 26:
                $primeAnciennete = ceil(($salaireCategoriel * 25) / 100);
                break;
        }
        return $primeAnciennete;
    }

    public function getGratification(int $personal): float|int
    {
        $today = Carbon::now();
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $salaireCategoriel = $personal->getCategorie()->getAmount();
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
        }
        $dureeSalarie = ceil(($dateEmbauche->diff($today)->days) / 30);
        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux();
        if ($dureeSalarie < 12) {
            $nombreJourTravailler = ceil(($dateEmbauche->diff($today)->days) / 360);
            $gratification = ceil((($salaireCategoriel * $tauxGratification) / 100) * $nombreJourTravailler);
        } else {
            $gratification = ceil(($salaireCategoriel * $tauxGratification) / 100);
        }
        return $gratification;
    }

    public function calculerImpotBrut(int $personal): float|int
    {
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $salaire = $personal->getSalary()->getBrutImposable();
        }
        $tranchesImposition = [
            ['min' => 0, 'limite' => 75000, 'taux' => 0],
            ['min' => 75001, 'limite' => 240000, 'taux' => 0.16],
            ['min' => 240001, 'limite' => 800000, 'taux' => 0.21],
            ['min' => 800001, 'limite' => 2400000, 'taux' => 0.24],
            ['min' => 2400001, 'limite' => 8000000, 'taux' => 0.28],
            ['min' => 8000001, 'limite' => PHP_INT_MAX, 'taux' => 0.32],
        ];

        $impotBrut = 0;

        foreach ($tranchesImposition as $tranche) {
            $limiteMin = $tranche['min'];
            $limiteMax = $tranche['limite'];
            $taux = $tranche['taux'];

            if ($salaire > $limiteMin && $salaire >= $limiteMax) {
                $montantImposable = ($limiteMax - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
            } else if ($salaire > $limiteMin && $salaire < $limiteMax) {
                $montantImposable = ($salaire - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
                break;
            }
        }

        return $impotBrut;
    }

    function calculateCreditImpot(float $nbrePart): float|int
    {
        $creditImpot = null;
        switch ($nbrePart) {
            case 1;
                $creditImpot = 0;
                break;
            case 1.5;
                $creditImpot = 5500;
                break;
            case 2;
                $creditImpot = 11000;
                break;
            case 2.5;
                $creditImpot = 16500;
                break;
            case 3;
                $creditImpot = 22000;
                break;
            case 3.5;
                $creditImpot = 27500;
                break;
            case 4;
                $creditImpot = 33000;
                break;
            case 4.5;
                $creditImpot = 38500;
                break;
            case 5;
                $creditImpot = 44000;
                break;
        }
        return $creditImpot;
    }

    public function getEnfantCharge(int $personal): array
    {
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $enfantCharge = $personal->getChargePeople();
            foreach ($enfantCharge as $enfant) {
                $non = $enfant->getFirstName();
                $prenoms = $enfant->getLastName();
                $dateNaissance = $enfant->getBirthday();
                $genre = $enfant->getGender();
            }
        }

        return [
            'nomEnfant' => $non,
            'prenomsEnfant' => $prenoms,
            'dateNaissanceEnfant' => date_format($dateNaissance, 'd/m/Y'),
            'genreEnfant' => $genre
        ];
    }

    public function getConjoint(int $personal): void
    {
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $conjoint = $personal->getConjoint();
        }
    }
}