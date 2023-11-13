<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Impots\ChargePersonals;
use App\Repository\Impots\ChargePersonalsRepository;
use Doctrine\ORM\EntityManagerInterface;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;

    public function __construct(EntityManagerInterface $manager,ChargePersonalsRepository $chargePersonalsRepository)
    {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
    }

    public function chargePersonal(Personal $personal): void
    {
        $impotBrut = $this->calculerImpotBrut($personal);
        $creditImpot = $this->calculateCreditImpot($personal);
        $impotNet = $impotBrut - $creditImpot;
        $amountCNPS = $this->calculateCNPS($personal);
        $amountCMU = $this->calculateCMU();
        $charge = $this->chargePersonalRt->findOneBy(['personal' => $personal]);
        if(!$charge){
            $charge = (new ChargePersonals())
                ->setPersonal($personal)
                ->setAmountIts($impotNet)
                ->setAmountCMU($amountCMU)
                ->setAmountCNPS($amountCNPS)
                ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS)
            ;
        }
            $charge
                ->setPersonal($personal)
                ->setAmountIts($impotNet)
                ->setAmountCMU($amountCMU)
                ->setAmountCNPS($amountCNPS)
                ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        $this->manager->persist($charge);
    }

    public function getParts(Personal $personal): float|int
    {
        $nbrePart = [
            'CELIBATAIRE' => 1,
            'MARIE' => 2,
            'VEUF' => 1,
            'DIVORCE' => 1
        ];
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();

        //Ici je voudrais mouvementé le nbre de part du salarié en fonction du nombre d'enfant à charge
        if ($personal->getEtatCivil() === 'CELIBATAIRE' || $personal->getEtatCivil() === 'DIVORCE') {
            return match (true) {
                $chargePeople == 1 => 2,
                $chargePeople == 2 => 2.5,
                $chargePeople == 3 => 3,
                $chargePeople == 4 => 3.5,
                $chargePeople == 5 => 4,
                $chargePeople == 6 => 4.5,
                $chargePeople > 6 => 5,
                default => 1,
            };
        } elseif ($personal->getEtatCivil() === 'MARIE') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 2,
            };
        } elseif ($personal->getEtatCivil() === 'VEUF') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 1,
            };
        }
        return $nbrePart[$personal->getEtatCivil()];
    }

    public function calculerImpotBrut(Personal $personal): float|int
    {
        $salaire = $personal->getSalary()->getBrutImposable();
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

    function calculateCreditImpot(Personal $personal): float|int
    {
        $nbrePart = $this->getParts($personal);
        $creditImpot = 0;
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

    public function calculateCNPS(Personal $personal): float
    {
        return $personal->getSalary()->getBrutImposable() * 0.063;
    }

    public function calculateCMU(): float
    {
        return 500;
    }

}