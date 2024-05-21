<?php

namespace App\Service;


use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Impots\ChargeEmployeur;
use App\Entity\Impots\ChargePersonals;
use App\Entity\Paiement\Campagne;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Service\CasExeptionel\PaieOutService;
use App\Service\PaieService\PaieByPeriodService;
use App\Service\PaieService\PaieProrataService;
use App\Service\PaieService\PaieServices;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class SalaryImpotsService implements SalaryInterface
{

    private EntityManagerInterface $manager;
    private ChargePersonalsRepository $chargePersonalRt;
    private ChargeEmployeurRepository $chargeEmployeurRt;
    private PaieServices $paieServices;
    private PaieOutService $outServices;

    public function __construct(
        EntityManagerInterface               $manager,
        ChargePersonalsRepository            $chargePersonalsRepository,
        ChargeEmployeurRepository            $chargeEmployeurRepository,
        PaieServices                         $paieServices,
        PaieOutService                       $paieOutService,
        private readonly PaieByPeriodService $paieByPeriodService,
        private readonly CampagneRepository  $campagneRepository,
        private readonly PaieProrataService  $paieProrataService
    )
    {
        $this->manager = $manager;
        $this->chargePersonalRt = $chargePersonalsRepository;
        $this->chargeEmployeurRt = $chargeEmployeurRepository;
        $this->paieServices = $paieServices;
        $this->outServices = $paieOutService;
    }

    /**
     * @throws Exception
     */
    public function chargePersonal(Personal $personal, Campagne $campagne): void
    {
        $previousCampagne = $this->campagneRepository->findLast();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $dateOfMonth = (new DateTime())->format('Y-m-d');
        $last_day_pr_camp = $previousCampagne?->getDateFin();

        if (($dateEmbauche > $dateOfMonth) && $dateEmbauche < $campagne->getStartedAt()) {
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
        } elseif (($dateEmbauche > $previousCampagne?->getStartedAt()) && $dateEmbauche <= $last_day_pr_camp) {
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
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $fullDate = new DateTime();
        $day = 1;
        $month = $fullDate->format('m');
        $year = $fullDate->format('Y');
        $dateOfMonth = new DateTime($day . '-' . $month . '-' . $year);
        $last_day_pr_camp = null;
        if ($previousCampagne) {
            $last_month = $previousCampagne->getStartedAt()->format('m');
            $last_day_pr_camp = new DateTime(31 . '-' . $last_month . '-' . $year);
        }
        if (($dateEmbauche > $dateOfMonth) && $dateEmbauche < $campagne->getStartedAt()) {
            $montantIs = $this->paieByPeriodService->amountISCampagne($personal, $campagne);
            $montantFPC = $this->paieByPeriodService->amountFPCCampagne($personal, $campagne);
            $montantFPCAnnuel = $this->paieByPeriodService->amountFPCAnnuelCampagne($personal, $campagne);
            $montantTA = $this->paieByPeriodService->amountTACampagne($personal, $campagne);
            $montantCR = $this->paieByPeriodService->amountCRCampagne($personal, $campagne);
            $montantPF = $this->paieByPeriodService->amountPFCampagne($personal);
            $montantAT = $this->paieByPeriodService->amountATCampagne($personal);
            $montantCMU = $this->paieByPeriodService->amountCMUEmpCampagne();
        } elseif (($dateEmbauche > $previousCampagne?->getStartedAt()) && $dateEmbauche <= $last_day_pr_camp) {
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
     * @param Departure $departure
     * @throws Exception
     */
    public function chargPersonalOut(Departure $departure): void
    {
        $majoration = (int)$this->outServices->getMajorations($departure);
        $prime_anciennete = (int)$this->outServices->getPrimeAncien($departure);
        $brut_imposable = (int)$this->outServices->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;

        $parts = $this->outServices->getNombrePart($departure);
        $impot_brut = (int)$this->outServices->getImpotBrut($departure);
        $credit_impot = (int)$this->outServices->getCreditImpot($departure);

        $impot_net = $impot_brut - $credit_impot;
        if ($net_imposable <= 75000 || $impot_net < 0) {
            $impot_net = 0;
        }

        $amount_cnps = (int)$this->outServices->getCnps($departure);
        $amount_cmu = (int)$this->outServices->getCmu($departure);
        $total_charge = $impot_net + $amount_cnps + $amount_cmu;
        $charge = $this->chargePersonalRt->findOneBy(['personal' => $departure->getPersonal(), 'departure' => $departure]);
        if (!$charge) {
            $charge = (new ChargePersonals())
                ->setPersonal($departure->getPersonal())
                ->setDeparture($departure)
                ->setNumPart($parts)
                ->setAmountImpotBrut($impot_brut)
                ->setAmountCreditImpot($credit_impot)
                ->setAmountIts($impot_net)
                ->setAmountCMU($amount_cmu)
                ->setAmountCNPS($amount_cnps)
                ->setAmountTotalChargePersonal($total_charge);
        }
        $charge
            ->setPersonal($departure->getPersonal())
            ->setDeparture($departure)
            ->setNumPart($parts)
            ->setAmountImpotBrut($impot_brut)
            ->setAmountCreditImpot($credit_impot)
            ->setAmountIts($impot_net)
            ->setAmountCMU($amount_cmu)
            ->setAmountCNPS($amount_cnps)
            ->setAmountTotalChargePersonal($total_charge);
        $this->manager->persist($charge);
        $this->manager->flush();
    }

    /**
     * @throws Exception
     */
    public function chargEmployerOut(Departure $departure): void
    {
        $montant_is = $this->outServices->getIS($departure);
        $montant_cr = $this->outServices->getCnpsRetraite($departure);
        $montant_pf = $this->outServices->getPrestFamily($departure);
        $montant_at = $this->outServices->getAccidentWorks($departure);
        $montant_ta = $this->outServices->getTauxLearns($departure);
        $montant_fpc = $this->outServices->getFpc($departure);
        $montant_fpc_year = $this->outServices->getFpcAnnuel($departure);
        $montant_cmu = $this->outServices->getCmuEmployer();
        $total_rate_cnps = $montant_cr + $montant_pf + $montant_at;
        $total_charge = $montant_is + $montant_fpc + $montant_fpc_year + $montant_ta + $total_rate_cnps + $montant_cmu;
        $charge_employer = $this->chargeEmployeurRt->findOneBy(['personal' => $departure->getPersonal(), 'departure' => $departure]);
        if (!$charge_employer) {
            $charge_employer = (new ChargeEmployeur())
                ->setPersonal($departure->getPersonal())
                ->setDeparture($departure)
                ->setAmountIS($montant_is)
                ->setAmountCR($montant_cr)
                ->setAmountPF($montant_pf)
                ->setAmountAT($montant_at)
                ->setAmountCMU($montant_cmu)
                ->setAmountTA($montant_ta)
                ->setAmountFPC($montant_fpc)
                ->setAmountAnnuelFPC($montant_fpc_year)
                ->setTotalRetenuCNPS($total_rate_cnps)
                ->setTotalChargeEmployeur($total_charge);
        }
        $charge_employer
            ->setPersonal($departure->getPersonal())
            ->setDeparture($departure)
            ->setAmountIS($montant_is)
            ->setAmountCR($montant_cr)
            ->setAmountPF($montant_pf)
            ->setAmountAT($montant_at)
            ->setAmountCMU($montant_cmu)
            ->setAmountTA($montant_ta)
            ->setAmountFPC($montant_fpc)
            ->setAmountAnnuelFPC($montant_fpc_year)
            ->setTotalRetenuCNPS($total_rate_cnps)
            ->setTotalChargeEmployeur($total_charge);
        $this->manager->persist($charge_employer);
        $this->manager->flush();
    }

}
