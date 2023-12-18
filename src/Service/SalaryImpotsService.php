<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;
    private CategoryChargeRepository $CategoryChargeRt;
    private ChargeEmployeurRepository $chargeEmployeurRt;

    public function __construct(
        EntityManagerInterface    $manager,
        ChargePersonalsRepository $chargePersonalsRepository,
        CategoryChargeRepository  $categoryChargeRepository,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        CongeRepository           $congeRepository,
    )
    {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
        $this->CategoryChargeRt = $categoryChargeRepository;
        $this->chargeEmployeurRt = $chargeEmployeurRepository;
    }

    public function chargePersonal(Personal $personal): void
    {
        $part = $this->getParts($personal);
        $impotBrut = $this->calculerImpotBrut($personal);
        $creditImpot = $this->calculateCreditImpot($personal);
        $impotNet = $impotBrut - $creditImpot;
        $amountCNPS = $this->calculateCNPS($personal);
        $amountCMU = $this->calculateCMU($personal);
        $charge = $this->chargePersonalRt->findOneBy(['personal' => $personal]);
        if (!$charge) {
            $charge = (new ChargePersonals())
                ->setPersonal($personal)
                ->setNumPart($part)
                ->setAmountIts($impotNet)
                ->setAmountCMU($amountCMU)
                ->setAmountCNPS($amountCNPS)
                ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        }
        $charge
            ->setPersonal($personal)
            ->setNumPart($part)
            ->setAmountIts($impotNet)
            ->setAmountCMU($amountCMU)
            ->setAmountCNPS($amountCNPS)
            ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        $this->manager->persist($charge);
    }

    public function chargeEmployeur(Personal $personal): void
    {
        $montantIs = $this->calculateIS($personal);
        $montantFDFP = $this->calculateFDFP($personal);
        $montantCR = $this->calculateRCNPS_CR($personal);
        $montantPF = $this->calculateRCNPS_PF($personal);
        $montantAT = $this->calculateRCNPS_AT($personal);
        $montantRetenuCNPS = $montantCR + $montantPF + $montantAT;
        $montantCMU = $this->calculateCMU($personal);
        $totalChargeEmployeur = $montantIs + $montantFDFP + $montantRetenuCNPS + $montantCMU;
        $chargeEmpl = $this->chargeEmployeurRt->findOneBy(['personal' => $personal]);
        if (!$chargeEmpl) {
            $chargeEmpl = (new ChargeEmployeur())
                ->setPersonal($personal)
                ->setAmountIS($montantIs)
                ->setAmountFDFP($montantFDFP)
                ->setAmountCR($montantCR)
                ->setAmountPF($montantPF)
                ->setAmountAT($montantAT)
                ->setAmountCMU($montantCMU)
                ->setTotalRetenuCNPS($montantRetenuCNPS)
                ->setTotalChargeEmployeur($totalChargeEmployeur);
        }
        $chargeEmpl
            ->setPersonal($personal)
            ->setAmountIS($montantIs)
            ->setAmountFDFP($montantFDFP)
            ->setAmountCR($montantCR)
            ->setAmountPF($montantPF)
            ->setAmountAT($montantAT)
            ->setAmountCMU($montantCMU)
            ->setTotalRetenuCNPS($montantRetenuCNPS)
            ->setTotalChargeEmployeur($totalChargeEmployeur);

        $this->manager->persist($chargeEmpl);
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

    public function calculateCNPS(Personal $personal): float
    {
        $salaire = $personal->getSalary()->getBrutImposable();
        $categoryRate = $this->CategoryChargeRt->findOneBy(['codification' => 'CNPS']);
        return $salaire * $categoryRate->getValue() / 100;

    }

    public function calculateCMU(Personal $personal): float|int
    {
        $categoryRate = $this->CategoryChargeRt->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();
        $marie = $personal->getEtatCivil() === Status::MARIEE ? 1 : 0;
        $CMU = $categoryRate->getValue();
        return ($chargePeople * $CMU) + ($CMU * $marie) + $CMU;
    }


    private function calculateIS(Personal $personal): float|int
    {
        $salaireBrut = $personal->getSalary()->getBrutAmount();
        $categoryRate = $this->CategoryChargeRt->findOneBy(['codification' => 'IS']);
        return $salaireBrut * $categoryRate?->getValue() / 100;
    }

    private function calculateFDFP(Personal $personal): float|int
    {
        $salaireBrut = $personal->getSalary()->getBrutAmount();
        $categoryRateFDFP_TA = $this->CategoryChargeRt->findOneBy(['codification' => 'FDFP_TA']);
        $categoryRateFDFP_FPC = $this->CategoryChargeRt->findOneBy(['codification' => 'FDFP_FPC']);
        $categoryRateFDFP_FPC_VER = $this->CategoryChargeRt->findOneBy(['codification' => 'FDFP_FPC_VER']);

        $montantFDFP_TA = $salaireBrut * $categoryRateFDFP_TA->getValue() / 100;
        $montantFDFP_FPC = $salaireBrut * $categoryRateFDFP_FPC->getValue() / 100;
        $montantFDFP_FPC_VER = $salaireBrut * $categoryRateFDFP_FPC_VER->getValue() / 100;

        return $montantFDFP_TA + $montantFDFP_FPC + $montantFDFP_FPC_VER;
    }

    private function calculateRCNPS_CR(Personal $personal): float|int
    {
        $salaireBrut = $personal->getSalary()->getBrutAmount();
        $categoryRateRCNPS_CR = $this->CategoryChargeRt->findOneBy(['codification' => 'RCNPS_CR']);
        return $salaireBrut * $categoryRateRCNPS_CR->getValue() / 100;
    }

    private function calculateRCNPS_PF(Personal $personal): float|int
    {
        $salaireBrut = $personal->getSalary()->getBrutAmount();
        $categoryRateRCNPS_PF = $this->CategoryChargeRt->findOneBy(['codification' => 'RCNPS_PF']);
        return $salaireBrut * $categoryRateRCNPS_PF->getValue() / 100;
    }

    private function calculateRCNPS_AT(Personal $personal): float|int
    {
        $salaireBrut = $personal->getSalary()->getBrutAmount();
        $categoryRateRCNPS_AT = $this->CategoryChargeRt->findOneBy(['codification' => 'RCNPS_AT']);
        return $salaireBrut * $categoryRateRCNPS_AT->getValue() / 100;
    }
}