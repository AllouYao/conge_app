<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Entity\Paiement\Campagne;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Service\CasExeptionel\DepartureCampagneService;
use App\Service\PaieService\PaieByPeriodService;
use App\Service\PaieService\PaieProrataService;
use App\Service\PaieService\PaieServices;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;
    private ChargeEmployeurRepository $chargeEmployeurRt;
    private PaieServices $paieServices;
    private DepartureCampagneService $departureCampagneService;

    public function __construct(
        EntityManagerInterface               $manager,
        ChargePersonalsRepository            $chargePersonalsRepository,
        ChargeEmployeurRepository            $chargeEmployeurRepository,
        PaieServices                         $paieServices,
        DepartureCampagneService             $departureCampagneService,
        private readonly PaieByPeriodService $paieByPeriodService,
        private readonly CampagneRepository  $campagneRepository,
        private readonly PaieProrataService  $paieProrataService
    )
    {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
        $this->chargeEmployeurRt = $chargeEmployeurRepository;
        $this->paieServices = $paieServices;
        $this->departureCampagneService = $departureCampagneService;
    }

    /**
     * @throws Exception
     */
    public function chargePersonal(Personal $personal, Campagne $campagne): void
    {
        $previousCampagne = $this->campagneRepository->findLast();
        if ($personal->getContract()->getDateEmbauche() > $campagne->getDateDebut() && $personal->getContract()->getDateEmbauche() <= $campagne->getDateFin()) {
            $part = $this->paieByPeriodService->getPartCampagne($personal);
            $impotBrut = $this->paieByPeriodService->amountImpotBrutCampagne($personal, $campagne);
            $creditImpot = $this->paieByPeriodService->amountCreditImpotCampagne($personal);
            $salaire = $this->paieByPeriodService->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
            $majorationHeursSupp = $this->paieByPeriodService->amountHeureSuppProrata($personal, $campagne);
            $netImposable = $salaire['brut_imposable_amount'] + $majorationHeursSupp;
            $impotNet = $impotBrut - $creditImpot;
            if ($netImposable <= 75000 || $impotNet < 0) {
                $impotNet = 0;
            }
            $amountCNPS = $this->paieByPeriodService->amountCNPSCampagne($personal, $campagne);
            $amountCMU = $this->paieByPeriodService->amountCMUCampagne($personal);
        } elseif (($personal->getContract()->getDateEmbauche() > $previousCampagne?->getStartedAt()) && $previousCampagne) {
            $part = $this->paieProrataService->nombrePart($personal);
            $impotBrut = $this->paieProrataService->amountImpotBrut($personal, $campagne);
            $creditImpot = $this->paieProrataService->amountCreditImpot($personal);
            $salaire = $this->paieProrataService->amountBrutAndAmountImposableAndAmountCategoriel($personal, $campagne);
            $majorationHeursSupp = $this->paieProrataService->amountHeureSupplementaire($personal, $campagne);
            $netImposable = $salaire['brut_imposable_amount'] + $majorationHeursSupp;
            $impotNet = $impotBrut - $creditImpot;
            if ($netImposable <= 75000 || $impotNet < 0) {
                $impotNet = 0;
            }
            $amountCNPS = $this->paieProrataService->amountCNPS($personal, $campagne);
            $amountCMU = $this->paieProrataService->amountCMU($personal);
        } else {
            $part = $this->paieServices->getPartCampagne($personal);
            $impotBrut = $this->paieServices->amountImpotBrutCampagne($personal, $campagne);
            $creditImpot = $this->paieServices->amountCreditImpotCampagne($personal);
            $salaire = $this->paieServices->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
            $majorationHeursSupp = $this->paieServices->getHeureSuppCampagne($personal, $campagne);
            $primeAnciennete = $this->paieServices->getPrimeAncienneteCampagne($personal, $campagne);
            $congesPayes = $this->paieServices->getAmountCongesPayes($personal); // Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation conges du mois actuel.
            $netImposable = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
            $impotNet = $impotBrut - $creditImpot;
            if ($netImposable <= 75000 || $impotNet < 0) {
                $impotNet = 0;
            }
            $amountCNPS = $this->paieServices->amountCNPSCampagne($personal, $campagne);
            $amountCMU = $this->paieServices->amountCMUCampagne($personal);
        }
        $totalCharges = $impotNet + $amountCMU + $amountCNPS;
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
                ->setAmountTotalChargePersonal($totalCharges);
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

    /**
     * @throws Exception
     */
    public function chargeEmployeur(Personal $personal, Campagne $campagne): void
    {
        $previousCampagne = $this->campagneRepository->findLast();
        if ($personal->getContract()->getDateEmbauche() > $campagne->getDateDebut() && $personal->getContract()->getDateEmbauche() <= $campagne->getDateFin()) {
            $montantIs = $this->paieByPeriodService->amountISCampagne($personal, $campagne);
            $montantFPC = $this->paieByPeriodService->amountFPCCampagne($personal, $campagne);
            $montantFPCAnnuel = $this->paieByPeriodService->amountFPCAnnuelCampagne($personal, $campagne);
            $montantTA = $this->paieByPeriodService->amountTACampagne($personal, $campagne);
            $montantCR = $this->paieByPeriodService->amountCRCampagne($personal, $campagne);
            $montantPF = $this->paieByPeriodService->amountPFCampagne($personal);
            $montantAT = $this->paieByPeriodService->amountATCampagne($personal);
            $montantCMU = $this->paieByPeriodService->amountCMUEmpCampagne();
        } elseif (($personal->getContract()->getDateEmbauche() > $previousCampagne?->getStartedAt()) && $previousCampagne) {
            $montantIs = $this->paieProrataService->amountIS($personal, $campagne);
            $montantTA = $this->paieProrataService->amountTA($personal, $campagne);
            $montantFPC = $this->paieProrataService->amountFPC($personal, $campagne);
            $montantFPCAnnuel = $this->paieProrataService->amountFPCAnnuel($personal, $campagne);
            $montantCR = $this->paieProrataService->amountCR($personal, $campagne);
            $montantPF = $this->paieProrataService->amountPF($personal);
            $montantAT = $this->paieProrataService->amountAT($personal);
            $montantCMU = $this->paieProrataService->amountCMUEmp();
        } else {
            $montantIs = $this->paieServices->amountISCampagne($personal, $campagne);
            $montantFPC = $this->paieServices->amountFPCCampagne($personal, $campagne);
            $montantFPCAnnuel = $this->paieServices->amountFPCAnnuelCampagne($personal, $campagne);
            $montantTA = $this->paieServices->amountTACampagne($personal, $campagne);
            $montantCR = $this->paieServices->amountCRCampagne($personal, $campagne);
            $montantPF = $this->paieServices->amountPFCampagne($personal);
            $montantAT = $this->paieServices->amountATCampagne($personal);
            $montantCMU = $this->paieServices->amountCMUEmpCampagne();
        }
        $montantRetenuCNPS = $montantCR + $montantPF + $montantAT;
        $totalChargeEmployeur = $montantIs + $montantFPC + $montantFPCAnnuel + $montantTA + $montantRetenuCNPS + $montantCMU;
        $chargeEmpl = $this->chargeEmployeurRt->findOneBy(['personal' => $personal]);
        if (!$chargeEmpl) {
            $chargeEmpl = (new ChargeEmployeur())
                ->setPersonal($personal)
                ->setAmountIS($montantIs)
                ->setAmountTA($montantTA)
                ->setAmountFPC($montantFPC)
                ->setAmountAnnuelFPC($montantFPCAnnuel)
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

    /**
     * @throws Exception
     */
    public function chargePersonalByDeparture(Personal $personal, Campagne $campagne): void
    {
        $netImposable = $this->departureCampagneService->netImposableByNbDayOfPresence($personal->getDepartures());
        $part = $this->departureCampagneService->nbPartCampagneByDeparture($personal->getDepartures());
        $impotBrut = $this->departureCampagneService->amountImpotBrutCampagneByDeparture($personal->getDepartures(), $campagne);
        $creditImpot = $this->departureCampagneService->amountCreditImpotCampagneByDeparture($personal->getDepartures());
        $impotNet = $impotBrut - $creditImpot;
        if ($netImposable <= 75000 || $impotNet < 0) {
            $impotNet = 0;
        }
        $amountCNPS = $this->departureCampagneService->amountCNPSCampagneByDeparture($personal->getDepartures(), $campagne);
        $amountCMU = $this->departureCampagneService->amountCMUCampagneByDeparture($personal->getDepartures());
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

    /**
     * @throws Exception
     */
    public function chargeEmployeurByDeparture(Personal $personal, Campagne $campagne): void
    {
        $montantIs = $this->departureCampagneService->amountISCampagneByDeparture($personal->getDepartures(), $campagne);
        $montantCR = $this->departureCampagneService->amountCRCampagneByDeparture($personal->getDepartures(), $campagne);
        $montantPF = $this->departureCampagneService->amountPFCampagneByDeparture($personal->getDepartures());
        $montantAT = $this->departureCampagneService->amountATCampagneByDeparture($personal->getDepartures());
        $montantTA = $this->departureCampagneService->amountTACampagneByDeparture($personal->getDepartures(), $campagne);
        $montantFPC = $this->departureCampagneService->amountFPCCampagneByDeparture($personal->getDepartures(), $campagne);
        $montantFPCAnnuel = $this->departureCampagneService->amountFPCAnnuelCampagneByDeparture($personal->getDepartures(), $campagne);
        $montantCMU = $this->departureCampagneService->amountCMUEmpCampagneByDeparture();
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

}
