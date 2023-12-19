<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Service\EtatService;
use App\Service\HeureSupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api_reporting', name: 'api_reporting_')]
class ApiReportingController extends AbstractController
{
    private PayrollRepository $payrollRepository;
    private EtatService $etatService;
    private HeureSupService $heureSupService;
    private CongeRepository $congeRepository;
    private CategoryChargeRepository $categoryChargeRepository;

    public function __construct(
        PayrollRepository        $payrollRepository,
        EtatService              $etatService,
        HeureSupService          $heureSupService,
        CongeRepository          $congeRepository,
        CategoryChargeRepository $categoryChargeRepository,
    )
    {
        $this->payrollRepository = $payrollRepository;
        $this->etatService = $etatService;
        $this->heureSupService = $heureSupService;
        $this->congeRepository = $congeRepository;
        $this->categoryChargeRepository = $categoryChargeRepository;
    }

    #[Route('/etat_salaire', name: 'etat_salaire', methods: ['GET'])]
    public function salaire(Request $request): JsonResponse
    {
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $personalID = (int)$request->get('personalsId');
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $salaries = $this->payrollRepository->findEtatSalaire($startAt, $endAt, $personalID);
        foreach ($salaries as $index => $salary) {
            $primeAnciennete = $this->etatService->getPrimeAnciennete($salary['personal_id']);
            $amountHeureSupp = $this->heureSupService->getAmountHeursSuppByID($salary['personal_id']);
            $gratification = $this->etatService->getGratification($salary['personal_id']);
            $conges = $this->congeRepository->getLastCongeByID($salary['personal_id']);
            $allocationConger = $conges?->getAllocationConge();
            $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $categoryRateIS = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $categoryRCNPS_AT = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $categoryRCNPS_PF = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $salaireBrut = $salary['brutAmount'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $salaireImposable = $salary['imposableAmount'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $retenueDivers = $salary['salaryCmu'] + $salary['salarySante'];
            $retenueCNPS = ($salaireImposable * $categoryRateRCNPS_CR) / 100;
            $itsPatronal = ($salaireImposable * $categoryRateIS) / 100;
            $tauxApprentissage = ($salaireImposable * $categoryRateFDFP_TA) / 100;
            $tfc = ($salaireImposable * $categoryRateFDFP_FPC) / 100;
            $accidentTravail = ($salaireImposable * $categoryRCNPS_AT) / 100;
            $prestationTravail = ($salaireImposable * $categoryRCNPS_PF) / 100;
            $totalRetenue = $retenueCNPS + $itsPatronal + $tauxApprentissage + $tfc + $accidentTravail + $prestationTravail;
            $salaireNet = $salaireBrut - $totalRetenue;
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($salary['createdAt'], 'd/m/Y'),
                'fullName' => $salary['firstName'] . ' ' . $salary['lastName'],
                'matricule' => $salary['matricule'],
                'salaireBase' => (int)$salary['baseAmount'],
                'primeAnciennete' => $primeAnciennete,
                'autrePrimes' => (int)$salary['prime_juridique'],
                'amountHeureSupp' => (int)$amountHeureSupp,
                'gratification' => (int)$gratification,
                'congePaye' => (int)$allocationConger,
                'salaireBrut' => (int)$salaireBrut,
                'personalCnps' => (int)$salary['salaryCnps'],
                'salaireImposable' => (int)$salaireImposable,
                'itsNetCreditImpot' => (int)$salary['salaryIts'],
                'prêtDuMois' => 0,
                'retenueDivers' => (int)$retenueDivers,
                'autreDroits' => 0,
                'salaireNet' => (int)$salaireNet,
                'observation' => '',
                'cnpsPatronal' => (int)$retenueCNPS,
                'itsPatronal' => (int)$itsPatronal,
                'tauxApprentissage' => (int)$tauxApprentissage,
                'TFC' => (int)$tfc,
                'accidentTravail' => (int)$accidentTravail,
                'prestationFamille' => (int)$prestationTravail
            ];
        }


        return new JsonResponse($data);
    }

    #[Route('/declaration_dgi', name: 'declaration_dgi', methods: ['GET'])]
    public function declarationDgi(Request $request): JsonResponse
    {
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationDgi = $this->payrollRepository->findEtatSalaire($startAt, $endAt, $personalID);
        foreach ($declarationDgi as $index => $declaration) {
            $transportNomImposable = 30000;
            $primeAnciennete = $this->etatService->getPrimeAnciennete($declaration['personal_id']);
            $amountHeureSupp = $this->heureSupService->getAmountHeursSuppByID($declaration['personal_id']);
            $gratification = $this->etatService->getGratification($declaration['personal_id']);
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id']);
            $allocationConger = $conges?->getAllocationConge();
            $itsSalarialBrut = $this->etatService->calculerImpotBrut($declaration['personal_id']);
            $creditImpot = $this->etatService->calculateCreditImpot((float)$declaration['numberPart']);
            $remunerationBrut = (int)$declaration['brutAmount'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $revenusNetImposable = (int)$declaration['imposableAmount'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $itsPatronal = ($revenusNetImposable * 1.2) / 100;
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['createdAt'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['firstName'] . ' ' . $declaration['lastName'],
                'remunerationBrut' => (int)$remunerationBrut,
                'indemniteTransportNomImposable' => $transportNomImposable,
                'autrePrimesEtIndemniteNomImposable' => (int)$declaration['prime_juridique'],
                'indemniteDepartNonImposable' => 0, // à mêtre à jour plus tards
                'AventageNature' => (int)$declaration['aventage_nature_imposable'],
                'revenusNetImposable' => (int)$revenusNetImposable,
                'itsSalarialBrut' => (int)$itsSalarialBrut,
                'nombreDeParts' => (double)$declaration['numberPart'],
                'creditImpot' => (int)$creditImpot,
                'itsSalarialNet' => (int)$declaration['salaryIts'],
                'itsPatronal' => (int)$itsPatronal
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/declaration_cnps', name: 'declaration_cnps', methods: ['GET'])]
    public function declarationCnps(Request $request): JsonResponse
    {
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationCnps = $this->payrollRepository->findEtatSalaire($startAt, $endAt, $personalID);
        foreach ($declarationCnps as $index => $declaration) {
            $primeAnciennete = $this->etatService->getPrimeAnciennete($declaration['personal_id']);
            $amountHeureSupp = $this->heureSupService->getAmountHeursSuppByID($declaration['personal_id']);
            $gratification = $this->etatService->getGratification($declaration['personal_id']);
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id']);
            $allocationConger = $conges?->getAllocationConge();
            $revenusNetImposable = (int)$declaration['imposableAmount'] + (int)$primeAnciennete + (int)$amountHeureSupp + (int)$gratification + (int)$allocationConger;
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['createdAt'], 'd/m/Y'),
                'numeroCnps' => $declaration['refCNPS'],
                'nom' => $declaration['firstName'],
                'prenoms' => $declaration['lastName'],
                'anneeNaissance' => $declaration['personal_birthday'],
                'dateEmbauche' => date_format($declaration['embauche'], 'd/m/Y'),
                'dateDepart' => '',
                'typeSalarie' => 'Mensuel',
                'revenusNetImposable' => $revenusNetImposable,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/declaration_fdfp', name: 'declaration_fdfp', methods: ['GET'])]
    public function declarationFdfp(Request $request): JsonResponse
    {
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationFdfp = $this->payrollRepository->findEtatSalaire($startAt, $endAt, $personalID);
        foreach ($declarationFdfp as $index => $declaration) {
            $primeAnciennete = $this->etatService->getPrimeAnciennete($declaration['personal_id']);
            $amountHeureSupp = $this->heureSupService->getAmountHeursSuppByID($declaration['personal_id']);
            $gratification = $this->etatService->getGratification($declaration['personal_id']);
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id']);
            $allocationConger = $conges?->getAllocationConge();
            $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $revenusNetImposable = (int)$declaration['imposableAmount'] + (int)$primeAnciennete + (int)$amountHeureSupp + (int)$gratification + (int)$allocationConger;
            $tauxApprentissage = ($revenusNetImposable * $categoryRateFDFP_TA) / 100;
            $tfc = ($revenusNetImposable * $categoryRateFDFP_FPC) / 100;
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['createdAt'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['firstName'] . '' . $declaration['lastName'],
                'revenusNetImposable' => $revenusNetImposable,
                'taux_apprentissage' => (int)$tauxApprentissage,
                'tfc' => (int)$tfc
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/declaration_cmu', name: 'declaration_cmu', methods: ['GET'])]
    public function declarationCmu(Request $request): JsonResponse
    {
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationCmu = $this->payrollRepository->findCnps($startAt, $endAt, $personalID);
        foreach ($declarationCmu as $index => $declaration) {

            $data[] = [

            ];
        }
        return new JsonResponse($data);
    }
}