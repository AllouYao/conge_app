<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\ChargePeopleRepository;
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
use App\Service\PaieService\PaieServices;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api_reporting', name: 'api_reporting_')]
class ApiReportingController extends AbstractController
{
    private PayrollRepository $payrollRepository;
    private EtatService $etatService;
    private PrimesRepository $primesRepository;
    private DetailSalaryRepository $detailSalaryRepository;
    private PersonalRepository $personalRepository;
    private DetailPrimeSalaryRepository $detailPrimeSalaryRepository;
    private CampagneRepository $campagneRepository;
    private ChargePeopleRepository $chargePeopleRepository;
    private PaieServices $paieServices;

    public function __construct(
        PayrollRepository           $payrollRepository,
        EtatService                 $etatService,
        PrimesRepository            $primesRepository,
        DetailSalaryRepository      $detailSalaryRepository,
        PersonalRepository          $personalRepository,
        DetailPrimeSalaryRepository $detailPrimeSalaryRepository,
        CampagneRepository          $campagneRepository,
        ChargePeopleRepository      $chargePeopleRepository,
        PaieServices                $paieServices
    )
    {
        $this->payrollRepository = $payrollRepository;
        $this->etatService = $etatService;
        $this->primesRepository = $primesRepository;
        $this->detailSalaryRepository = $detailSalaryRepository;
        $this->personalRepository = $personalRepository;
        $this->detailPrimeSalaryRepository = $detailPrimeSalaryRepository;
        $this->campagneRepository = $campagneRepository;
        $this->chargePeopleRepository = $chargePeopleRepository;
        $this->paieServices = $paieServices;
    }

    #[Route('/prime_indemnite', name: 'prime_indemnite', methods: ['GET'])]
    public function primeIndemnite(): JsonResponse
    {
        $personals = $this->personalRepository->findAllPersonalOnCampain();
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
    public function etatSalaireGbl(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $personal_id = (int)$request->get('personalsId');
        $years = (int)$request->get('year');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data_payroll = [];
        if ($this->isGranted('ROLE_RH')) {
            $salaries = $this->payrollRepository->findEtatSalaireClone($month_request, $years, $personal_id);
        } else {
            $salaries = $this->payrollRepository->findEtatSalaireByRoleEmployer($month_request, $years, $personal_id);
        }

        foreach ($salaries as $index => $salary) {
            $url_salary = $this->generateUrl('campagne_bulletin_incatif', ['uuid' => $salary['payroll_uuid']]);
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date_salary = $salary['periode_debut'];
            $periode = $formatter->format($date_salary);
            $nb_jr_travailler = $salary['dayOfPresence'];
            $data_payroll[] = [
                'index' => ++$index,
                'dateCreation' => date_format($salary['startedAt'], 'd/m/Y'),
                'day_of_presence' => $nb_jr_travailler,
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
                'print_bulletin' => $url_salary,
                'regul_moins_percus' => (int)$salary['remboursNet'] + (int)$salary['remboursBrut'],
                'regul_plus_percus' => (int)$salary['retenueNet'] + $salary['retenueBrut'],
                'amount_pret_mensuel' => (int)$salary['amountMensualityPret'],
                'amount_acompte_mensuel' => (int)$salary['amountMensuelAcompt']
            ];
        }
        return new JsonResponse($data_payroll);
    }

    /**
     * @throws Exception
     */
    #[Route('/etat_virements_annuel', name: 'etat_virements_annuel', methods: ['GET'])]
    public function etatVirementAnl(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $personal_id = (int)$request->get('personalsId');
        $years = (int)$request->get('year');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data_virement = [];
        if ($this->isGranted('ROLE_RH')) {
            $request_viremts = $this->payrollRepository->findPayrollVirementAnnuel(Status::VIREMENT, false, true, $month_request, $years, $personal_id);
        } else {
            $request_viremts = $this->payrollRepository->findPayrollVirementAnnuelByRoleEmployeur(Status::VIREMENT, false, true, $month_request, $years, $personal_id);
        }
        foreach ($request_viremts as $virement) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date_viremts = $virement['debut'];
            $periode = $formatter->format($date_viremts);
            $data_virement[] = [
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
        return new JsonResponse($data_virement);
    }

    #[Route('/element_variable', name: 'element_variable', methods: ['GET'])]
    public function etatVariable(): JsonResponse
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

            $mois_persus_net = $payrollBefore?->getRemboursNet();
            $mois_persus_brut = $payrollBefore?->getRemboursBrut();
            $plus_persus_net = $payrollBefore?->getRetenueNet();
            $plus_persus_brut = $payrollBefore?->getRetenueBrut();
            $amountBrutBefore = $payrollBefore?->getBrutAmount() + $plus_persus_brut - ($mois_persus_brut);
            $amountBrutLast = $payrollLast?->getBrutAmount();
            $amountNetBefore = $payrollBefore?->getNetPayer() + $plus_persus_net - ($mois_persus_net);
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
    public function etatCaisse(): JsonResponse
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
    #[Route('/etat_caisse_annuel', name: 'etat_versement_caisse_annuel', methods: ['GET'])]
    public function etatCaisseAnnel(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $personal_id = (int)$request->get('personalsId');
        $years = (int)$request->get('year');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data_request = [];
        if ($this->isGranted('ROLE_RH')) {
            $request_caisse = $this->payrollRepository->findPayrollVirementAnnuel(Status::CAISSE, false, true, $month_request, $years, $personal_id);
        } else {
            $request_caisse = $this->payrollRepository->findPayrollVirementAnnuelByRoleEmployeur(Status::CAISSE, false, true, $month_request, $years, $personal_id);
        }
        foreach ($request_caisse as $index => $virement) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $date_formater = $virement['debut'];
            $periode = $formatter->format($date_formater);
            $data_request[] = [
                'ordre' => ++$index,
                'emmolution' => 'ESPECE',
                'name_salaried' => $virement['nom_salaried'] . ' ' . $virement['prenoms_salaried'],
                'salaire_net' => (double)$virement['net_payes'],
                'periode' => $periode,
                'emargement' => '',
                'stations' => $virement['station']
            ];
        }
        return new JsonResponse($data_request);
    }


    #[Route('/declaration_dgi/current/month', name: 'declaration_dgi_current_month', methods: ['GET'])]
    public function declarationMonthDgi(): JsonResponse
    {
        $data = [];
        $declarationDgi = $this->payrollRepository->findSalarialeCampagne(true);
        if (!$declarationDgi) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationDgi as $index => $declaration) {
            $itsSalarialBrut = $this->etatService->calculerImpotBrut($declaration['personal_id']);
            $creditImpot = $this->etatService->calculateCreditImpot((float)$declaration['numberPart']);
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['periode_debut'], 'd/m/Y'),
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


    #[Route('/declaration_dgi', name: 'declaration_dgi', methods: ['GET'])]
    public function declarationDgi(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $years = (int)$request->get('year');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationDgi = $this->payrollRepository->findEtatDeclaration($month_request, $years, $personalID);
        foreach ($declarationDgi as $index => $declaration) {
            $itsSalarialBrut = $this->etatService->calculerImpotBrut($declaration['personal_id']);
            $creditImpot = $this->etatService->calculateCreditImpot((float)$declaration['numberPart']);
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['periode_debut'], 'd/m/Y'),
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
        $data = [];
        $declarationCnps = $this->payrollRepository->findSalarialeCampagne(true);
        if (!$declarationCnps) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationCnps as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['periode_debut'], 'd/m/Y'),
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

    #[Route('/declaration_cnps', name: 'declaration_cnps', methods: ['GET'])]
    public function declarationCnps(Request $request): JsonResponse
    {
        $month = $request->get('months');
        $year = (int)$request->get('year');
        $personalID = (int)$request->get('personalsId');

        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }

        $data = [];
        $declarationCnps = $this->payrollRepository->findEtatDeclaration($month, $year, $personalID);
        foreach ($declarationCnps as $index => $declaration) {

            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['periode_debut'], 'd/m/Y'),
                'numeroCnps' => $declaration['refCNPS'],
                'nom' => $declaration['nom'],
                'prenoms' => $declaration['prenoms'],
                'anneeNaissance' => $declaration['personal_birthday'],
                'dateEmbauche' => date_format($declaration['dateEmbauche'], 'd/m/Y'),
                'dateDepart' => '',
                'typeSalarie' => 'Mensuel',
                'revenusNetImposable' => $declaration['imposableAmount'],
            ];
        }

        return new JsonResponse($data);
    }

    /**
     */
    #[Route('/declaration_fdfp/current/month', name: 'declaration_fdfp_current_month', methods: ['GET'])]
    public function declarationMonthFdfp(): JsonResponse
    {
        $data = [];
        $declarationFdfp = $this->payrollRepository->findSalarialeCampagne(true);
        if (!$declarationFdfp) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationFdfp as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'date_ouverture' => date_format($declaration['periode_debut'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['nom'] . '' . $declaration['prenoms'],
                'revenusNetImposable' => (int)$declaration['imposableAmount'],
                'taux_apprentissage' => (int)$declaration['amountTA'],
                'tfc' => (int)$declaration['amountFPC']
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/declaration_fdfp', name: 'declaration_fdfp', methods: ['GET'])]
    public function declarationFdfp(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $personal_id = (int)$request->get('personalsId');
        $year = (int)$request->get('year');
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['data' => []]);
        }
        $data = [];
        $declarationFdfp = $this->payrollRepository->findEtatDeclaration($month_request, $year, $personal_id);
        foreach ($declarationFdfp as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'dateCreation' => date_format($declaration['periode_debut'], 'd/m/Y'),
                'matricule' => $declaration['matricule'],
                'fullName' => $declaration['nom'] . '' . $declaration['prenoms'],
                'revenusNetImposable' => (int)$declaration['imposableAmount'],
                'taux_apprentissage' => (int)$declaration['amountTA'],
                'tfc' => (int)$declaration['amountFPC']
            ];
        }

        return new JsonResponse($data);
    }


    #[Route('/etat_salariale_mensuel', name: 'salariale_etat', methods: ['GET'])]
    public function etatSalarialeMensuel(): JsonResponse
    {
        $data = [];
        $requestEtatSalary = $this->payrollRepository->findSalarialeCampagne(true);
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

    #[Route('/declaration_cmu/current/month', name: 'declaration_cmu_current_month', methods: ['GET'])]
    public function declarationMonthCmu(): JsonResponse
    {
        $data = [];
        $declarationCmu = $this->payrollRepository->findCampagneCmuNew(true);
        if (!$declarationCmu) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationCmu as $index => $declaration) {
            $chargePeoples = $this->chargePeopleRepository->findBy(['personal' => $declaration['personal_id'], 'isCmu' => true]);
            if ($declaration['is_cmu'] == 1) {
                $data[] = [
                    'index' => ++$index,
                    'matricule' => $declaration['matricule'],
                    'num_cnps_assure' => $declaration['refCNPS'],
                    'num_sec_assure' => $declaration['num_ss'],
                    'nom_assure' => $declaration['nom'],
                    'pnom_assure' => $declaration['prenoms'],
                    'birthday' => $declaration['personal_birthday'],
                    'num_cnps_benef' => $declaration['refCNPS'],
                    'num_sec_benef' => $declaration['conjoint_num_ss'],
                    'type_benef' => 'C',
                    'full_name' => $declaration['conjoint_name'],
                    'birthday_benef' => '',
                    'genre_benef' => 'M',
                ];
            }
            if (count($chargePeoples) > 0) {
                foreach ($chargePeoples as $item) {
                    $data[] = [
                        'index' => ++$index,
                        'matricule' => $declaration['matricule'],
                        'num_cnps_assure' => $declaration['refCNPS'],
                        'num_sec_assure' => $declaration['num_ss'],
                        'nom_assure' => $declaration['nom'],
                        'pnom_assure' => $declaration['prenoms'],
                        'birthday' => $declaration['personal_birthday'],
                        'num_cnps_benef' => $declaration['refCNPS'],
                        'num_sec_benef' => $declaration['num_ss'],
                        'type_benef' => 'E',
                        'full_name' => $item->getFirstName() . ' ' . $item->getLastName(),
                        'birthday_benef' => $declaration['personal_birthday'],
                        'genre_benef' => $declaration['genre']
                    ];
                }
            }
            $data[] = [
                'index' => ++$index,
                'matricule' => $declaration['matricule'],
                'num_cnps_assure' => $declaration['refCNPS'],
                'num_sec_assure' => $declaration['num_ss'],
                'nom_assure' => $declaration['nom'],
                'pnom_assure' => $declaration['prenoms'],
                'birthday' => $declaration['personal_birthday'],
                'num_cnps_benef' => $declaration['refCNPS'],
                'num_sec_benef' => $declaration['num_ss'],
                'type_benef' => 'T',
                'full_name' => $declaration['nom'] . ' ' . $declaration['prenoms'],
                'birthday_benef' => $declaration['personal_birthday'],
                'genre_benef' => $declaration['genre']
            ];

        }
        return new JsonResponse($data);
    }

    #[Route('/declaration_cmu', name: 'declaration_cmu', methods: ['GET'])]
    public function declarationCmu(Request $request): JsonResponse
    {
        $month_request = $request->get('months');
        $personal_id = (int)$request->get('personalsId');
        $year = (int)$request->get('year');
        $data = [];
        $declarationCmu = $this->payrollRepository->findEtatDeclaration($month_request, $year, $personal_id);
        if (!$declarationCmu) {
            return $this->json(['data' => []]);
        }
        foreach ($declarationCmu as $index => $declaration) {
            $data[] = [
                'index' => ++$index,
                'matricule' => $declaration['matricule'],
                'num_cnps_assure' => $declaration['refCNPS'],
                'num_sec_assure' => $declaration['num_ss'],
                'nom_assure' => $declaration['nom'],
                'pnom_assure' => $declaration['prenoms'],
                'birthday' => $declaration['personal_birthday'],
                'num_cnps_benef' => $declaration['refCNPS'],
                'num_sec_benef' => $declaration['num_ss'],
                'type_benef' => 'T',
                'nom_benef' => $declaration['nom'],
                'pnom_benef' => $declaration['prenoms'],
                'birthday_benef' => $declaration['personal_birthday'],
                'genre_benef' => $declaration['genre']
            ];

            $chargePeoples = $this->chargePeopleRepository->findPeopleAssureByPersonalId($declaration['personal_id']);
            if (!empty($chargePeoples->getQuery()->getResult())) {
                foreach ($chargePeoples->getQuery()->getResult() as $chargePerson) {
                    $data[] = [
                        'index' => ++$index,
                        'matricule' => $declaration['matricule'],
                        'num_cnps_assure' => $declaration['refCNPS'],
                        'num_sec_assure' => $declaration['num_ss'],
                        'nom_assure' => $declaration['nom'],
                        'pnom_assure' => $declaration['prenoms'],
                        'birthday' => $declaration['personal_birthday'],
                        'num_cnps_benef' => $declaration['refCNPS'],
                        'num_sec_benef' => $chargePerson->getNumss(),
                        'type_benef' => 'E',
                        'nom_benef' => $chargePerson->getFirstName(),
                        'pnom_benef' => $chargePerson->getLastName(),
                        'birthday_benef' => date_format($chargePerson->getBirthday(), 'd/m/y'),
                        'genre_benef' => $chargePerson->getGender(),
                    ];
                }
            }
            if ($declaration['is_cmu'] !== null) {
                $data[] = [
                    'index' => ++$index,
                    'matricule' => $declaration['matricule'],
                    'num_cnps_assure' => $declaration['refCNPS'],
                    'num_sec_assure' => $declaration['num_ss'],
                    'nom_assure' => $declaration['nom'],
                    'pnom_assure' => $declaration['prenoms'],
                    'birthday' => $declaration['personal_birthday'],
                    'num_cnps_benef' => $declaration['refCNPS'],
                    'num_sec_benef' => $declaration['conjoint_num_ss'],
                    'type_benef' => 'C',
                    'nom_benef' => $declaration['conjoint_name'],
                    'pnom_benef' => $declaration['conjoint_name'],
                    'birthday_benef' => '',
                    'genre_benef' => $declaration['genre'] = 'M' ? 'FEMME' : 'HOMME',
                ];
            }
        }
        return new JsonResponse($data);
    }

    #[Route('/declaration_disa/current/year', name: 'declaration_disa_current_year', methods: ['GET'])]
    public function declarationDisaYear(): JsonResponse
    {
        $today = Carbon::today();
        $year = $today->year;
        $data = [];
        $declarationDisa = $this->payrollRepository->findDisaCurrentYear($year);
        if (!$declarationDisa) {
            return $this->json(['data' => []]);
        }
        $firstMonth = new DateTime();
        $firstMonth->setDate($firstMonth->format('Y'), 1, 1);
        $monthWork = $today->diff($firstMonth)->m;
        foreach ($declarationDisa as $index => $disa) {
            if ($disa['date_depart']) {
                $monthWork = $disa['date_depart']->diff($firstMonth)->m;
            }
            $amountPf = $disa['smig'] * $monthWork;
            $data[] = [
                'index' => ++$index,
                'matricule' => $disa['matricule'],
                'nom&prenoms' => $disa['nom'] . $disa['prenoms'],
                'num_cnps' => $disa['refCNPS'] ?? '',
                'birthday' => $disa['personal_birthday'],
                'hire_day' => $disa['date_embauche'] ? date_format($disa['date_embauche'], 'd/m/y') : '',
                'Dismiss_day' => $disa['date_depart'] ?? '',
                'type_salarie' => 'M',
                'salary_brut_annuel' => $disa['imposable_amount'],
                'work_month' => (int)$monthWork,
                'salary_with_pf_at' => $amountPf,
                'salary_with_retraite' => $disa['imposable_amount'],
                'choice_sociale' => '123'
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/remuneration_brute', name: 'remuneration_brute', methods: ['GET'])]
    public function etatRbm(): JsonResponse
    {
        $data = [];
        $remunerations = $this->payrollRepository->findSalarialeCampagne(true);
        foreach ($remunerations as $index => $remuneration) {
            $etat_civil = null;
            if($remuneration['etatCivil'] === Status::CELIBATAIRE) {
                $etat_civil = 'C';
            }elseif ($remuneration['etatCivil'] === Status::DIVORCE) {
                $etat_civil = 'D';
            }elseif ($remuneration['etatCivil'] === Status::MARIEE) {
                $etat_civil = 'M';
            }elseif ($remuneration['etatCivil'] === Status::VEUF) {
                $etat_civil = 'V';
            }
            $personal = $this->personalRepository->findOneBy(['id' => $remuneration['personal_id']]);
            $creditImpot = $this->paieServices->amountCreditImpotCampagne($personal);
            $autrePrime = (int)$remuneration['amountPrimePanier'] + $remuneration['amountPrimeSalissure'] + $remuneration['amountPrimeOutillage'] + $remuneration['amountPrimeRendement'] +$remuneration['amountPrimeTenueTrav'];
            $data[] = [
                'index' => ++$index,
                'num_cnps' => $remuneration['refCNPS'],
                'nom_prenoms' => $remuneration['nom'] .' '. $remuneration['prenoms'],
                'type_work' => 'Salarié',
                'emp_q' => $remuneration['emploie'],
                'code_emp' => 'EQ',
                'regime' => 'G',
                'genre' => $remuneration['genre'] === Status::MASCULIN ? 'M':'F',
                'nationalite' => 'I',
                'local' => 'L',
                'etat_civil' => $etat_civil,
                'nb_enfant' => $remuneration['nb_enfant'],
                'nbPart' => $remuneration['numberPart'],
                'day_work' => $remuneration['day_work'],
                'salary_brut' => $remuneration['imposableAmount'],
                'amount_avantage_bareme' => $remuneration['aventageNonImposable'],
                'amount_avantage_reelle' => $remuneration['amountAvantageImposable'],
                'total_brut' => $remuneration['aventageNonImposable'] + $remuneration['amountAvantageImposable'] + $remuneration['imposableAmount'],
                'revenu_non_imposable' => $autrePrime,
                'brut_imposable' => $remuneration['aventageNonImposable'] + $remuneration['amountAvantageImposable'] + $remuneration['imposableAmount'],
                'credit_impot' => $creditImpot,
                'its_salarie_brut' => $remuneration['salaryIts'] + $creditImpot,
                'its_salarie_net' => $remuneration['salaryIts'],
                'exonere_amount' => $remuneration['salaryTransport'],
                'exonere_designation' => 'TRANSPORT',
            ];
        }
        return new JsonResponse($data);
    }
}