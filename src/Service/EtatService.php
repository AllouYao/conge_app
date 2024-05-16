<?php

namespace App\Service;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;

class EtatService
{
    private PrimesRepository $primesRepository;
    private PersonalRepository $personalRepository;

    public function __construct(PrimesRepository $primesRepository, PersonalRepository $personalRepository)
    {
        $this->primesRepository = $primesRepository;
        $this->personalRepository = $personalRepository;
    }

    public function getPrimeAnciennete($personal): float|int
    {
        $salaireCategoriel = null;
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $salaireCategoriel = $personal->getCategorie()->getAmount();
            $anciennete = $personal->getOlder();
        }
        if ($anciennete >= 2 && $anciennete < 3) {
            $primeAnciennete = $salaireCategoriel * 2 / 100;
        } elseif ($anciennete >= 3 && $anciennete <= 25) {
            $primeAnciennete = ($salaireCategoriel * $anciennete) / 100;
        } elseif ($anciennete >= 26) {
            $primeAnciennete = ($salaireCategoriel * 25) / 100;

        } else {
            $primeAnciennete = 0;
        }
        return ceil($primeAnciennete);
    }

    public function getGratification(int $personal): float|int
    {
        $anciennete = null;
        $salaireCategoriel = null;
        $personals = $this->personalRepository->findBy(['id' => $personal]);
        foreach ($personals as $personal) {
            $salaireCategoriel = $personal->getCategorie()->getAmount();
            $anciennete = $personal->getOlder();
        }
        $dureeSalarie = $anciennete * 12;
        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux();
        if ($dureeSalarie < 12) {
            $nombreJourTravailler = ceil($dureeSalarie * 30);
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

    public function getGratifications(mixed $olderDay, $salaireCategoriel): float|int
    {

        $days = $olderDay->days;
        $olderMonth = $days / 30;
        $tauxGratification = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux();
        if ($olderMonth < 12) {
            $gratification = ((($salaireCategoriel * $tauxGratification) / 100) * $days) / 360;
        } else {
            $gratification = ($salaireCategoriel * $tauxGratification) / 100;
        }
        return $gratification;
    }
}