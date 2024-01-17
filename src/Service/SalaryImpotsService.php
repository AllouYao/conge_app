<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use Doctrine\ORM\EntityManagerInterface;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;
    private ChargeEmployeurRepository $chargeEmployeurRt;
    private UtimePaiementService $utimePaiementService;

    public function __construct(
        EntityManagerInterface    $manager,
        ChargePersonalsRepository $chargePersonalsRepository,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        UtimePaiementService      $utimePaiementService
    )
    {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
        $this->chargeEmployeurRt = $chargeEmployeurRepository;
        $this->utimePaiementService = $utimePaiementService;
    }

    public function chargePersonal(Personal $personal): void
    {
        $part = $this->utimePaiementService->getNumberParts($personal);
        $impotBrut = $this->utimePaiementService->calculerAmountImpotBrut($personal);
        $creditImpot = $this->utimePaiementService->calculateAmountCreditImpot($personal);
        $impotNet = $impotBrut - $creditImpot;
        $amountCNPS = $this->utimePaiementService->calculateAmountCNPS($personal);
        $amountCMU = $this->utimePaiementService->calculateAmountCMU($personal);
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
        $this->manager->flush();
    }

    public function chargeEmployeur(Personal $personal): void
    {
        $montantIs = $this->utimePaiementService->calculateAmountIS($personal);
        $montantFPC = $this->utimePaiementService->calculateAmountFPC($personal);
        $montantFPCAnnuel = $this->utimePaiementService->calculateAmountFPCAnnuel($personal);
        $montantTA = $this->utimePaiementService->calculateAmountTA($personal);
        $montantCR = $this->utimePaiementService->calculateAmountRCNPS_CR($personal);
        $montantPF = $this->utimePaiementService->calculateAmountRCNPS_PF($personal);
        $montantAT = $this->utimePaiementService->calculateAmountRCNPS_AT($personal);
        $montantRetenuCNPS = $montantCR + $montantPF + $montantAT;
        $montantCMU = $this->utimePaiementService->calculateAmountCMU($personal);
        $totalChargeEmployeur = $montantIs + $montantFPC + $montantFPCAnnuel + $montantTA + $montantRetenuCNPS + $montantCMU;
        $chargeEmpl = $this->chargeEmployeurRt->findOneBy(['personal' => $personal]);
        if (!$chargeEmpl) {
            $chargeEmpl = (new ChargeEmployeur())
                ->setPersonal($personal)
                ->setAmountIS($montantIs)
                ->setAmountCR($montantCR)
                ->setAmountPF($montantPF)
                ->setAmountAT($montantAT)
                ->setAmountCMU($montantCMU)
                ->setAmountTA($montantTA)
                ->setAmountFPC($montantFPC)
                ->setAmountAnnuelFPC($montantFPCAnnuel)
                ->setTotalRetenuCNPS($montantRetenuCNPS)
                ->setTotalChargeEmployeur($totalChargeEmployeur);
        }
        $chargeEmpl
            ->setPersonal($personal)
            ->setAmountIS($montantIs)
            ->setAmountCR($montantCR)
            ->setAmountPF($montantPF)
            ->setAmountAT($montantAT)
            ->setAmountCMU($montantCMU)
            ->setAmountTA($montantTA)
            ->setAmountFPC($montantFPC)
            ->setAmountAnnuelFPC($montantFPCAnnuel)
            ->setTotalRetenuCNPS($montantRetenuCNPS)
            ->setTotalChargeEmployeur($totalChargeEmployeur);

        $this->manager->persist($chargeEmpl);
        $this->manager->flush();
    }
}