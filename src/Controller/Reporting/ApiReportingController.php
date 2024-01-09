<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\EtatService;
use App\Service\HeureSupService;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
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
    private PrimesRepository $primesRepository;
    private DetailSalaryRepository $detailSalaryRepository;
    private PersonalRepository $personalRepository;

    public function __construct(
        PayrollRepository        $payrollRepository,
        EtatService              $etatService,
        HeureSupService          $heureSupService,
        CongeRepository          $congeRepository,
        CategoryChargeRepository $categoryChargeRepository,
        PrimesRepository         $primesRepository,
        DetailSalaryRepository   $detailSalaryRepository,
        PersonalRepository       $personalRepository
    )
    {
        $this->payrollRepository = $payrollRepository;
        $this->etatService = $etatService;
        $this->heureSupService = $heureSupService;
        $this->congeRepository = $congeRepository;
        $this->categoryChargeRepository = $categoryChargeRepository;
        $this->primesRepository = $primesRepository;
        $this->detailSalaryRepository = $detailSalaryRepository;
        $this->personalRepository = $personalRepository;
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/prime_indemnite', name: 'prime_indemnite', methods: ['GET'])]
    public function primeIndemnite(): JsonResponse
    {
        $personals = $this->personalRepository->findAllPersonal();
        $personalPrime = [];
        foreach ($personals as $value => $personal) {
            $primePanier = $this->primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
            $primeSalissure = $this->primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
            $primeTT = $this->primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
            $primeOutil = $this->primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
            $amountPanier = $this->detailSalaryRepository->findPrime($personal, $primePanier);
            $amountSalissure = $this->detailSalaryRepository->findPrime($personal, $primeSalissure);
            $amountTT = $this->detailSalaryRepository->findPrime($personal, $primeTT);
            $amountOutil = $this->detailSalaryRepository->findPrime($personal, $primeOutil);
            $primeTransport = $personal->getSalary()->getPrimeTransport() === 30000 ? $personal->getSalary()->getPrimeTransport() : 30000;
            $primeAnciennete = $this->etatService->getPrimeAnciennete($personal->getId());
            $personalPrime[] = [
                "index" => ++$value,
                "fullName" => $personal->getFirstName() . ' ' . $personal->getLastName(),
                "prime_transport" => $primeTransport,
                "prime_panier" => (int)$amountPanier?->getAmountPrime(),
                "prime_salissure" => (int)$amountSalissure?->getAmountPrime(),
                "prime_outillage" => (int)$amountOutil?->getAmountPrime(),
                "prime_tenue_travail" => (int)$amountTT?->getAmountPrime(),
                "prime_fonction" => (int)$personal->getSalary()?->getPrimeFonction(),
                "prime_logement" => (int)$personal->getSalary()?->getPrimeLogement(),
                "aventage_nature" => (int)$personal->getSalary()->getAvantage()?->getTotalAvantage(),
                "prime_anciennete" => (int)$primeAnciennete ?? 0
            ];
        }
        return new JsonResponse($personalPrime);
    }

    #[Route('/etat_salaire_globale', name: 'etat_salaire', methods: ['GET'])]
    public function etatSalarialeGlobale(Request $request): JsonResponse
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
                'dateCreation' => date_format($salary['startedAt'], 'd/m/Y'),
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
                'dateCreation' => date_format($declaration['startedAt'], 'd/m/Y'),
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
                'dateCreation' => date_format($declaration['startedAt'], 'd/m/Y'),
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
                'dateCreation' => date_format($declaration['startedAt'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['firstName'] . '' . $declaration['lastName'],
                'revenusNetImposable' => $revenusNetImposable,
                'taux_apprentissage' => (int)$tauxApprentissage,
                'tfc' => (int)$tfc
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/declaration_cmu', name: 'declaration_cmu_global', methods: ['GET'])]
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

    #[Route('/declaration_dgi/current/month', name: 'declaration_dgi_current_month', methods: ['GET'])]
    public function declarationMonthDgi(): JsonResponse
    {
        $currentFullDate = new \DateTime('now');

        $data = [];

        $declarationDgi = $this->payrollRepository->findEtatSalaireCurrentMonth(true, $currentFullDate);
        if (!$declarationDgi) {
            return $this->json(['data' => []]);
        }


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
                'nombreDeParts' => (float)$declaration['numberPart'],
                'creditImpot' => (int)$creditImpot,
                'itsSalarialNet' => (int)$declaration['salaryIts'],
                'itsPatronal' => (int)$itsPatronal
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/declaration_cnps/current/month', name: 'declaration_cnps_current_month', methods: ['GET'])]
    public function declarationMonthCnps(): JsonResponse
    {
        $currentFullDate = new \DateTime('now');

        $data = [];

        $declarationCnps = $this->payrollRepository->findEtatSalaireCurrentMonth(true, $currentFullDate);

        if (!$declarationCnps) {
            return $this->json(['data' => []]);
        }


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

    #[Route('/declaration_fdfp/current/month', name: 'declaration_fdfp_current_month', methods: ['GET'])]
    public function declarationMonthFdfp(): JsonResponse
    {
        $currentFullDate = new \DateTime('now');

        $data = [];

        $declarationFdfp = $this->payrollRepository->findEtatSalaireCurrentMonth(true, $currentFullDate);

        if (!$declarationFdfp) {
            return $this->json(['data' => []]);
        }
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
    public function declarationMonthCmu(Request $request): JsonResponse
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

            $data[] = [];
        }
        return new JsonResponse($data);
    }

    #[Route('/etat_salariale_mensuel', name: 'salariale_etat', methods: ['GET'])]
    public function etatSalarialeMensuel(): JsonResponse
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $data = [];
        $salariesEtat = $this->payrollRepository->findSalarialeCampagne(true, $year, $month);
        foreach ($salariesEtat as $index => $salary) {
            $primeAnciennete = $this->etatService->getPrimeAnciennete($salary['personal_id']);
            $amountHeureSupp = 0;
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
}