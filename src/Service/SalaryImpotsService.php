<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Personal;
use App\Entity\ElementVariable\VariablePaie;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Entity\Paiement\Campagne;
use App\Repository\ElementVariable\VariablePaieRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use Doctrine\ORM\EntityManagerInterface;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;
    private ChargeEmployeurRepository $chargeEmployeurRt;
    private UtimePaiementService $utimePaiementService;
    private VariablePaieRepository $paieRepository;
    private DepartServices $departServices;

    public function __construct(
        EntityManagerInterface    $manager,
        ChargePersonalsRepository $chargePersonalsRepository,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        UtimePaiementService      $utimePaiementService,
        VariablePaieRepository    $paieRepository,
        DepartServices            $departServices
    ) {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
        $this->chargeEmployeurRt = $chargeEmployeurRepository;
        $this->utimePaiementService = $utimePaiementService;
        $this->paieRepository = $paieRepository;
        $this->departServices = $departServices;
    }

    public function chargePersonal(Personal $personal, Campagne $campagne): void
    {
        $part = $this->utimePaiementService->getNumberParts($personal);
        $impotBrut = $this->utimePaiementService->calculerAmountImpotBrut($personal, $campagne);
        $creditImpot = $this->utimePaiementService->calculateAmountCreditImpot($personal);
        /** @var  $netImposable */
        $salaire = $this->utimePaiementService->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->utimePaiementService->getAmountMajorationHeureSupp($personal, $campagne);
        $primeAnciennete = $this->utimePaiementService->getAmountAnciennete($personal);
        $congesPayes = $this->utimePaiementService->getAmountCongesPayes($personal);
        $netImposable = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $impotNet = $impotBrut - $creditImpot;
        if ($netImposable <= 75000 || $impotNet < 0) {
            $impotNet = 0;
        }
        $amountCNPS = $this->utimePaiementService->calculateAmountCNPS($personal, $campagne);
        $amountCMU = $this->utimePaiementService->calculateAmountCMU($personal);
        $charge = $this->chargePersonalRt->findOneBy(['personal' => $personal]);
        if (!$charge) {
            $charge = (new ChargePersonals())
                ->setPersonal($personal)
                ->setNumPart($part)
                ->setAmountImpotBrut($impotBrut)
                ->setAmountCreditImpot($creditImpot)
                ->setAmountIts($impotNet)
                ->setAmountCMU($amountCMU)
                ->setAmountCNPS($amountCNPS)
                ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        }
        $charge
            ->setPersonal($personal)
            ->setNumPart($part)
            ->setAmountImpotBrut($impotBrut)
            ->setAmountCreditImpot($creditImpot)
            ->setAmountIts($impotNet)
            ->setAmountCMU($amountCMU)
            ->setAmountCNPS($amountCNPS)
            ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        $this->manager->persist($charge);
        $this->manager->flush();
    }

    public function chargePersonalByDeparture(Personal $personal, Campagne $campagne): void
    {
        $netImposable = (double)$personal->getDepartures()->getTotalIndemniteImposable();
        $part = $this->utimePaiementService->getNumberParts($personal);
        $impotBrut = $this->departServices->calculerImpotBrutDeparture($personal->getDepartures());
        $creditImpot = $this->departServices->calculateCreditImpotDeparture($personal->getDepartures());
        $impotNet = $this->departServices->getAmountITS($personal->getDepartures());
        if ($netImposable <= 75000 || $impotNet < 0) {
            $impotNet = 0;
        }
        $amountCNPS = $this->departServices->getAmountCNPS($personal->getDepartures());
        $amountCMU = $this->departServices->getAmountCMU($personal->getDepartures());
        $charge = $this->chargePersonalRt->findOneBy(['personal' => $personal, 'departure' => $personal->getDepartures()]);
        if (!$charge) {
            $charge = (new ChargePersonals())
                ->setPersonal($personal)
                ->setDeparture($personal->getDepartures())
                ->setNumPart($part)
                ->setAmountImpotBrut($impotBrut)
                ->setAmountCreditImpot($creditImpot)
                ->setAmountIts($impotNet)
                ->setAmountCMU($amountCMU)
                ->setAmountCNPS($amountCNPS)
                ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        }
        $charge
            ->setPersonal($personal)
            ->setDeparture($personal->getDepartures())
            ->setNumPart($part)
            ->setAmountImpotBrut($impotBrut)
            ->setAmountCreditImpot($creditImpot)
            ->setAmountIts($impotNet)
            ->setAmountCMU($amountCMU)
            ->setAmountCNPS($amountCNPS)
            ->setAmountTotalChargePersonal($impotNet + $amountCMU + $amountCNPS);
        $this->manager->persist($charge);
        $this->manager->flush();
    }

    public function chargeEmployeur(Personal $personal, Campagne $campagne): void
    {
        $montantIs = $this->utimePaiementService->calculateAmountIS($personal, $campagne);
        $montantFPC = $this->utimePaiementService->calculateAmountFPC($personal, $campagne);
        $montantFPCAnnuel = $this->utimePaiementService->calculateAmountFPCAnnuel($personal, $campagne);
        $montantTA = $this->utimePaiementService->calculateAmountTA($personal, $campagne);
        $montantCR = $this->utimePaiementService->calculateAmountRCNPS_CR($personal, $campagne);
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

    public function chargeEmployeurByDeparture(Personal $personal, Campagne $campagne): void
    {
        $montantIs = $this->departServices->getAmountIS($personal->getDepartures());
        $montantCR = $this->departServices->getAmountRCNPS_CR($personal->getDepartures());
        $montantPF = $this->departServices->getAmountRCNPS_PF($personal->getDepartures());
        $montantAT = $this->departServices->getAmountRCNPS_AT($personal->getDepartures());
        $montantTA = $this->departServices->getAmountTA($personal->getDepartures());
        $montantFPC = $this->departServices->getAmountFPC($personal->getDepartures());
        $montantFPCAnnuel = $this->departServices->getAmountFPCAnnuel($personal->getDepartures());
        $montantCMU = $this->departServices->getAmountCMU($personal->getDepartures());
        $montantRetenuCNPS = $montantCR + $montantPF + $montantAT;
        $totalChargeEmployeur = $montantIs + $montantFPC + $montantFPCAnnuel + $montantTA + $montantRetenuCNPS + $montantCMU;
        $chargeEmpl = $this->chargeEmployeurRt->findOneBy(['personal' => $personal, 'departure' => $personal->getDepartures()]);
        if (!$chargeEmpl) {
            $chargeEmpl = (new ChargeEmployeur())
                ->setPersonal($personal)
                ->setDeparture($personal->getDepartures())
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
            ->setDeparture($personal->getDepartures())
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

    public function variableElement(Personal $personal): void
    {
        $contract = $personal->getContract();
        $embauche = $contract->getDateEmbauche();
        $salary = $personal->getSalary();
        $smig = $salary->getSmig();
        $variablePaie = $this->paieRepository->findOneBy(['personal' => $personal]);
        if (!$variablePaie) {
            $variablePaie = (new VariablePaie())
                ->setPersonal($personal)
                ->setEmbauche($embauche)
                ->setEtatCivil($personal->getEtatCivil())
                ->setSmig($smig);
        }
        $variablePaie
            ->setPersonal($personal)
            ->setEmbauche($embauche)
            ->setEtatCivil($personal->getEtatCivil())
            ->setSmig($smig);
        $this->manager->persist($variablePaie);
    }
}
