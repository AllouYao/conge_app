<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\DetailPrimeSalaryRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\EtatService;
use App\Service\HeureSupService;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use IntlDateFormatter;
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
    private DetailPrimeSalaryRepository $detailPrimeSalaryRepository;
    private CampagneRepository $campagneRepository;

    public function __construct(
        PayrollRepository           $payrollRepository,
        EtatService                 $etatService,
        HeureSupService             $heureSupService,
        CongeRepository             $congeRepository,
        CategoryChargeRepository    $categoryChargeRepository,
        PrimesRepository            $primesRepository,
        DetailSalaryRepository      $detailSalaryRepository,
        PersonalRepository          $personalRepository,
        DetailPrimeSalaryRepository $detailPrimeSalaryRepository,
        CampagneRepository          $campagneRepository,
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
        $this->detailPrimeSalaryRepository = $detailPrimeSalaryRepository;
        $this->campagneRepository = $campagneRepository;
    }

    #[Route('/prime_indemnite', name: 'prime_indemnite', methods: ['GET'])]
    public function primeIndemnite(): JsonResponse
    {
        if ($this->isGranted('ROLE_RH')) {
            $personals = $this->personalRepository->findAllPersonalOnCampain();
        } else {
            $personals = $this->personalRepository->findAllPersonalByEmployeRole();
        }
        $personalPrime = [];
        foreach ($personals as $value => $personal) {
            $primePanier = $this->primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
            $primeSalissure = $this->primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
            $primeTT = $this->primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
            $primeOutil = $this->primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
            $primeRendement = $this->primesRepository->findOneBy(['code' => Status::PRIME_RENDEMENT]);
            $primeLogement = $this->primesRepository->findOneBy(['code' => Status::PRIME_LOGEMENT]);
            $primeFonction = $this->primesRepository->findOneBy(['code' => Status::PRIME_FONCTION]);
            $indemniteLogement = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_LOGEMENTS]);
            $indemniteFonction = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_FONCTION]);
            $amountPanier = $this->detailSalaryRepository->findPrime($personal, $primePanier);
            $amountSalissure = $this->detailSalaryRepository->findPrime($personal, $primeSalissure);
            $amountTT = $this->detailSalaryRepository->findPrime($personal, $primeTT);
            $amountOutil = $this->detailSalaryRepository->findPrime($personal, $primeOutil);
            $amountRendement = $this->detailSalaryRepository->findPrime($personal, $primeRendement);
            $amountPrimeLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeLogement);
            $amountPrimeFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeFonction);
            $amountIndemniteLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteLogement);
            $amountIndemniteFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteFonction);
            $primeTransport = $this->primesRepository->findOneBy(['code' => Status::PRIME_TRANSPORT])?->getTaux();
            $primeTransportImposable = (int)$personal->getSalary()->getPrimeTransport() - (int)$primeTransport;
            $primeAnciennete = $this->etatService->getPrimeAnciennete($personal);
            $amountTotalAvantage = $personal->getSalary()->getAvantage()?->getTotalAvantage();
            $amountAvantage = $personal->getSalary()?->getAmountAventage();
            $amountAvantageImposable = $amountAvantage > $amountTotalAvantage ? $amountAvantage - $amountTotalAvantage : 0;
            $personalPrime[] = [
                "index" => ++$value,
                "fullName" => $personal->getFirstName() . ' ' . $personal->getLastName(),
                "prime_transport" => (int)$primeTransport,
                "prime_panier" => (int)$amountPanier?->getAmountPrime(),
                "prime_salissure" => (int)$amountSalissure?->getAmountPrime(),
                "prime_outillage" => (int)$amountOutil?->getAmountPrime(),
                "prime_tenue_travail" => (int)$amountTT?->getAmountPrime(),
                "prime_rendement" => (int)$amountRendement?->getAmountPrime(),
                "prime_fonction" => (int)$amountPrimeFonction?->getAmount(),
                "prime_logement" => (int)$amountPrimeLogement?->getAmount(),
                "indemnite_logement" => (int)$amountIndemniteLogement?->getAmount(),
                "indemnite_fonction" => (int)$amountIndemniteFonction?->getAmount(),
                "primeTransport_imposable" => $primeTransportImposable,
                "aventage_nature" => (int)$amountTotalAvantage,
                "aventage_nature_imposable" => (int)$amountAvantageImposable,
                "prime_anciennete" => (int)$primeAnciennete
            ];
        }
        return new JsonResponse($personalPrime);
    }


    /**
     * @throws Exception
     */
    #[Route('/etat_salaire_globale', name: 'etat_salaire', methods: ['GET'])]
    public function etatSalarialeGlobale(Request $request): JsonResponse
    {
        $dateRequest = $request->get('dateDebut');
        $startAt = $endAt = null;
        if ($dateRequest) {
            $dateRequestObj = DateTime::createFromFormat('Y-m', $dateRequest);
            $dateDebut = $dateRequestObj->format('Y-m-01');
            $dateFin = $dateRequestObj->format('Y-m-t');
            $startAt = new DateTime($dateDebut);
            $endAt = new DateTime($dateFin);
        }
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }


        $data = [];
        if ($this->isGranted('ROLE_RH')) {
            $salaries = $this->payrollRepository->findEtatSalaire($startAt, $endAt, $personalID);
        } else {
            $salaries = $this->payrollRepository->findEtatSalaireByRoleEmployer($startAt, $endAt, $personalID);
        }

        foreach ($salaries as $index => $salary) {
            $url = $this->generateUrl('campagne_bulletin_incatif', ['uuid' => $salary['personal_uuid']]);
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date = $salary['periode_debut'];
            $periode = $formatter->format($date);
            $nbJourTravailler = $salary['dayOfPresence'];
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($salary['startedAt'], 'd/m/Y'),
                'day_of_presence' => $nbJourTravailler,
                'nb_part' => $salary['numberPart'],
                'periode' => $periode,
                'nom_salarie' => $salary['nom'] . ' ' . $salary['prenoms'],
                'matricule' => $salary['matricule'],
                'service' => $salary['station'],
                'salaireBase' => (int)$salary['baseAmount'],
                'sursalaire_salaried' => (int)$salary['sursalaire'],
                'primeAnciennete' => (int)$salary['AncienneteAmount'],
                'prime_tenue_travail' => (int)$salary['amountPrimeTenueTrav'],
                'prime_salissure' => (int)$salary['amountPrimeSalissure'],
                'amountHeureSupp' => (int)$salary['majorationAmount'],
                'salaireImposable' => (int)$salary['imposableAmount'],
                'its_salaried' => (int)$salary['salaryIts'],
                'fixcale_salariale' => (int)$salary['fixcalAmount'],
                'cnps_salaried' => (int)$salary['salaryCnps'],
                'cmu_salaried' => (int)$salary['salaryCmu'],
                'charge_salarial' => (int)$salary['totalRetenueSalarie'],
                'prime_transport_legal' => (int)$salary['salaryTransport'],
                'assurance_salariale' => (int)$salary['salarySante'],
                'net_payer_salaried' => (int)$salary['netPayer'],
                'employer_is' => (int)$salary['employeurIs'],
                'amount_fdfp' => (int)$salary['employeurFdfp'],
                'employer_cr' => (int)$salary['employeurCr'],
                'employer_cmu' => (int)$salary['employeurCmu'],
                'employer_pr' => (int)$salary['employeurPf'],
                'employer_at' => (int)$salary['employeurAt'],
                'assurance_patronales' => (int)$salary['employeurSante'],
                'charge_patronal' => (int)$salary['totalRetenuePatronal'],
                'masse_salariale' => (int)$salary['masseSalary'],
                'print_bulletin' => $url,
                'regul_moins_percus' => (int)$salary['remboursNet'] + (int)$salary['remboursBrut'],
                'regul_plus_percus' => (int)$salary['retenueNet'] + $salary['retenueBrut'],
                'amount_pret_mensuel' => (int)$salary['amountMensualityPret'],
                'amount_acompte_mensuel' => (int)$salary['amountMensuelAcompt']
            ];
        }
        return new JsonResponse($data);
    }


    /**
     * @throws NonUniqueResultException
     */
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
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id'], false);
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

    /**
     * @throws NonUniqueResultException
     */
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
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id'], false);
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

    /**
     * @throws NonUniqueResultException
     */
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
            $conges = $this->congeRepository->getLastCongeByID($declaration['personal_id'], false);
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


    #[Route('/etat_salariale_mensuel', name: 'salariale_etat', methods: ['GET'])]
    public function etatSalarialeMensuel(): JsonResponse
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $data = [];
        $requestEtatSalary = $this->payrollRepository->findSalarialeCampagne(true, $year, $month);
        foreach ($requestEtatSalary as $index => $salaryEtat) {
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($salaryEtat['startedAt'], 'd/m/Y'),
                'full_name_salaried' => $salaryEtat['nom'] . ' ' . $salaryEtat['prenoms'],
                'matricule_salaried' => $salaryEtat['matricule'],
                'salaire_categoriel' => (int)$salaryEtat['baseAmount'],
                'prime_anciennete' => (int)$salaryEtat['AncienneteAmount'],
                'prime_fonction' => (int)$salaryEtat['primeFonctionAmount'],
                'prime_logement' => (int)$salaryEtat['primeLogementAmount'],
                'prime_indemnite_fonction' => (int)$salaryEtat['indemniteFonctionAmount'],
                'prime_indemnite_logement' => (int)$salaryEtat['indemniteLogementAmount'],
                'heure_supplementaire' => (int)$salaryEtat['majorationAmount'],
                'gratification' => null,
                'conges_payes' => (int)$salaryEtat['congesPayesAmount'],
                'salaire_brut' => (int)$salaryEtat['brutAmount'],
                'amount_cnps_salaried' => (int)$salaryEtat['salaryCnps'],
                'net_imposable' => (int)$salaryEtat['imposableAmount'],
                'amount_its_salaried' => (int)$salaryEtat['salaryIts'],
                'amount_cmu_salaried' => (int)$salaryEtat['salaryCmu'],
                'net_payes_amount' => (int)$salaryEtat['netPayer'],
                'amount_cnps_patronal' => (int)$salaryEtat['employeurCr'],
                'amount_is_patronal' => (int)$salaryEtat['employeurIs'],
                'amount_ta_patronal' => (int)$salaryEtat['amountTA'],
                'amount_fpc_patronal' => (int)$salaryEtat['amountFPC'],
                'amount_at_patronal' => (int)$salaryEtat['employeurAt'],
                'amount_pf_patronal' => (int)$salaryEtat['employeurPf']
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/declaration_dgi/current/month', name: 'declaration_dgi_current_month', methods: ['GET'])]
    public function declarationMonthDgi(): JsonResponse
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $data = [];
        $declarationDgi = $this->payrollRepository->findSalarialeCampagne(true, $year, $month);
        if (!$declarationDgi) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationDgi as $index => $declaration) {
            $itsSalarialBrut = $this->etatService->calculerImpotBrut($declaration['personal_id']);
            $creditImpot = $this->etatService->calculateCreditImpot((float)$declaration['numberPart']);
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['startedAt'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'full_name_salaried' => $declaration['nom'] . ' ' . $declaration['prenoms'],
                'remuneration_Brut' => (int)$declaration['brutAmount'],
                'indemniteTransportNomImposable' => (int)$declaration['salaryTransport'],
                'amount_prime_panier' => (int)$declaration['amountPrimePanier'],
                'amount_prime_salissure' => (int)$declaration['amountPrimeSalissure'],
                'amount_prime_outil' => (int)$declaration['amountPrimeOutillage'],
                'amount_prime_tt' => (int)$declaration['amountPrimeTenueTrav'],
                'amount_prime_rendement' => (int)$declaration['amountPrimeRendement'],
                'indemniteDepartNonImposable' => null, // à mêtre à jour plus tards
                'AventageNature' => (int)$declaration['aventageNonImposable'],
                'revenusNetImposable' => (int)$declaration['imposableAmount'],
                'itsSalarialBrut' => (int)$itsSalarialBrut,
                'nombreDeParts' => (float)$declaration['numberPart'],
                'creditImpot' => (int)$creditImpot,
                'itsSalarialNet' => (int)$declaration['salaryIts'],
                'itsPatronal' => (int)$declaration['employeurIs']
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/declaration_cnps/current/month', name: 'declaration_cnps_current_month', methods: ['GET'])]
    public function declarationMonthCnps(): JsonResponse
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $data = [];
        $declarationCnps = $this->payrollRepository->findSalarialeCampagne(true, $year, $month);
        if (!$declarationCnps) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationCnps as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['createdAt'], 'd/m/Y'),
                'numero_cnps' => $declaration['numCnps'],
                'nom' => $declaration['nom'],
                'prenoms' => $declaration['prenoms'],
                'annee_naissance' => $declaration['personal_birthday'],
                'date_embauche' => date_format($declaration['dateEmbauche'], 'd/m/Y'),
                'dateDepart' => '',
                'typeSalarie' => 'Mensuel',
                'anciennete' => ceil($declaration['older']),
                'revenusNetImposable' => (int)$declaration['imposableAmount'],
            ];
        }
        return new JsonResponse($data);
    }

    /**
     */
    #[Route('/declaration_fdfp/current/month', name: 'declaration_fdfp_current_month', methods: ['GET'])]
    public function declarationMonthFdfp(): JsonResponse
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $data = [];
        $declarationFdfp = $this->payrollRepository->findSalarialeCampagne(true, $year, $month);
        if (!$declarationFdfp) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationFdfp as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['startedAt'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['nom'] . '' . $declaration['prenoms'],
                'revenusNetImposable' => (int)$declaration['imposableAmount'],
                'taux_apprentissage' => (int)$declaration['amountTA'],
                'tfc' => (int)$declaration['amountFPC']
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/element_variable', name: 'element_variable', methods: ['GET'])]
    public function etatElementVariable(): JsonResponse
    {
        $dataElementVariable = [];
        $campainBefore = $this->campagneRepository->findBeforeLast();
        $campainLast = $this->campagneRepository->findLastCampagneForRecap();

        if (!$campainBefore) {
            return $this->json(['data' => []]);
        }
        $personals = $campainBefore->getPersonal();

        foreach ($personals as $index => $personal) {
            $payrollBefore = $this->payrollRepository->findOnePayroll($campainBefore, $personal);
            $payrollLast = $this->payrollRepository->findOnePayroll($campainLast, $personal);

            if (!$payrollBefore && !$payrollLast) {
                return $this->json(['data' => []]);
            }

            $amountBrutBefore = $payrollBefore?->getBrutAmount();
            $amountBrutLast = $payrollLast?->getBrutAmount();
            $amountNetBefore = $payrollBefore?->getNetPayer();
            $amountNetLast = $payrollLast?->getNetPayer();
            $amountEcartBrut = $amountBrutLast - $amountBrutBefore;
            $amountEcartNet = $amountNetLast - $amountNetBefore;

            $dataElementVariable[] = [
                'index' => ++$index,
                'matricule' => $personal->getMatricule(),
                'nom' => $personal->getFirstName(),
                'prenoms' => $personal->getLastName(),
                'before_brut_amount' => (int)$amountBrutBefore,
                'last_brut_amount' => (int)$amountBrutLast,
                'before_net_amount' => (int)$amountNetBefore,
                'last_net_amount' => (int)$amountNetLast,
                'ecart_brut_amount' => (int)$amountEcartBrut,
                'ecart_net_amount' => (int)$amountEcartNet
            ];
        }

        return new  JsonResponse($dataElementVariable);
    }

    /** Etat des versement (Virement et caisse) mensuel **/
    #[Route('/etat_virements', name: 'etat_virements', methods: ['GET'])]
    public function etatVersement(): JsonResponse
    {
        $dataVirement = [];
        if ($this->isGranted('ROLE_RH')) {
            $requestVirements = $this->payrollRepository->getPayrollVirement(Status::VIREMENT, true, true);
        } else {
            $requestVirements = $this->payrollRepository->getPayrollVirementByRoleEmployeur(Status::VIREMENT, true, true);
        }
        if (!$requestVirements) {
            return $this->json(['data' => []]);
        }

        foreach ($requestVirements as $virement) {
            $dataVirement[] = [
                'name_salaried' => $virement['nom_salaried'] . ' ' . $virement['prenoms_salaried'],
                'nom_banque' => $virement['name_banque'],
                'code_agence' => $virement['code_agence'],
                'code_banque' => $virement['code_compte'],
                'comptes' => $virement['num_compte'],
                'cles' => $virement['rib_compte'],
                'salaire_net' => (double)$virement['net_payes'],
            ];
        }
        return new JsonResponse($dataVirement);
    }

    #[Route('/etat_caisse', name: 'etat_versement_caisse', methods: ['GET'])]
    public function etatVersementCaisse(): JsonResponse
    {
        $dataCaisse = [];
        if ($this->isGranted('ROLE_RH')) {
            $requestVirements = $this->payrollRepository->getPayrollVirement(Status::CAISSE, true, true);
        } else {
            $requestVirements = $this->payrollRepository->getPayrollVirementByRoleEmployeur(Status::CAISSE, true, true);
        }
        if (!$requestVirements) {
            return $this->json(['data' => []]);
        }

        foreach ($requestVirements as $index => $virement) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date = $virement['debut'];
            $periode = $formatter->format($date);
            $dataCaisse[] = [
                'ordre' => ++$index,
                'emmolution' => 'ESPECE',
                'name_salaried' => $virement['nom_salaried'] . ' ' . $virement['prenoms_salaried'],
                'salaire_net' => (double)$virement['net_payes'],
                'periode' => $periode,
                'emargement' => '',
                'stations' => $virement['station']
            ];
        }
        return new JsonResponse($dataCaisse);
    }

    /**
     * @throws Exception
     */
    #[Route('/etat_virements_annuel', name: 'etat_virements_annuel', methods: ['GET'])]
    public function etatVirementAnnuel(Request $request): JsonResponse
    {
        $dateRequest = $request->get('dateDebut');
        $startAt = $endAt = null;
        if ($dateRequest) {
            $dateRequestObj = DateTime::createFromFormat('Y-m', $dateRequest);
            $dateDebut = $dateRequestObj->format('Y-m-01');
            $dateFin = $dateRequestObj->format('Y-m-t');
            $startAt = new DateTime($dateDebut);
            $endAt = new DateTime($dateFin);
        }
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        if ($this->isGranted('ROLE_RH')) {
            $requestVirements = $this->payrollRepository->findPayrollVirementAnnuel(Status::VIREMENT, false, true, $startAt, $endAt, $personalID);
        } else {
            $requestVirements = $this->payrollRepository->findPayrollVirementAnnuelByRoleEmployeur(Status::VIREMENT, false, true, $startAt, $endAt, $personalID);
        }
        foreach ($requestVirements as $virement) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date = $virement['debut'];
            $periode = $formatter->format($date);
            $data[] = [
                'name_salaried' => $virement['nom_salaried'] . ' ' . $virement['prenoms_salaried'],
                'nom_banque' => $virement['name_banque'],
                'code_agence' => $virement['code_agence'],
                'code_banque' => $virement['code_compte'],
                'comptes' => $virement['num_compte'],
                'cles' => $virement['rib_compte'],
                'salaire_net' => (double)$virement['net_payes'],
                'periode' => $periode,
            ];
        }
        return new JsonResponse($data);
    }

    /**
     * @throws Exception
     */
    #[Route('/etat_caisse_annuel', name: 'etat_versement_caisse_annuel', methods: ['GET'])]
    public function etatVersementCaisseAnnel(Request $request): JsonResponse
    {
        $dateRequest = $request->get('dateDebut');
        $startAt = $endAt = null;
        if ($dateRequest) {
            $dateRequestObj = DateTime::createFromFormat('Y-m', $dateRequest);
            $dateDebut = $dateRequestObj->format('Y-m-01');
            $dateFin = $dateRequestObj->format('Y-m-t');
            $startAt = new DateTime($dateDebut);
            $endAt = new DateTime($dateFin);
        }
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        if ($this->isGranted('ROLE_RH')) {
            $requestVirements = $this->payrollRepository->findPayrollVirementAnnuel(Status::CAISSE, false, true, $startAt, $endAt, $personalID);
        } else {
            $requestVirements = $this->payrollRepository->findPayrollVirementAnnuelByRoleEmployeur(Status::CAISSE, false, true, $startAt, $endAt, $personalID);
        }
        foreach ($requestVirements as $index => $virement) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date = $virement['debut'];
            $periode = $formatter->format($date);
            $data[] = [
                'ordre' => ++$index,
                'emmolution' => 'ESPECE',
                'name_salaried' => $virement['nom_salaried'] . ' ' . $virement['prenoms_salaried'],
                'salaire_net' => (double)$virement['net_payes'],
                'periode' => $periode,
                'emargement' => '',
                'stations' => $virement['station']
            ];
        }
        return new JsonResponse($data);
    }
}