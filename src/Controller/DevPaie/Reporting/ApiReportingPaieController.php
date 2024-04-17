<?php

namespace App\Controller\DevPaie\Reporting;

use App\Repository\DevPaie\OperationRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api_reporting_paie', name: 'api_reporting_paie_', methods: ['GET'])]
class ApiReportingPaieController extends AbstractController
{
    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly PayrollRepository   $payrollRepository,
        private readonly CampagneRepository $campagneRepository
    )
    {
    }

    #[Route('/remboursement_salaire', name: 'remboursement_salaire', methods: ['GET'])]
    public function remboursementSalaire(): JsonResponse
    {
        $today = Carbon::today();
        $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatus(Status::REMBOURSEMENT, [Status::EN_ATTENTE], $today->month, $today->year);
        $dataRemboursement = [];
        foreach ($requestOperationRemboursement as $ordre => $remboursement) {
            $dataRemboursement[] = [
                'ordre' => ++$ordre,
                'operation_id' => $remboursement['operation_id'],
                'date_remboursement' => $remboursement['date_operation'],
                'remboursement' => $remboursement['type_operations'],
                'matricule_salarie' => $remboursement['matricule_personal'],
                'nom_salarie' => $remboursement['name_personal'],
                'prenom_salarie' => $remboursement['lastname_personal'],
                'station_salarie' => $remboursement['stations_personal'],
                'remboursement_montant_brut' => (int)$remboursement['montant_brut'] ?? 0,
                'remboursement_montant-net' => (int)$remboursement['montant_net'] ?? 0,
                'remboursement_status' => $remboursement['status_operation']
            ];
        }

        return new JsonResponse($dataRemboursement);
    }

    #[Route('/remboursement_salaire_validate', name: 'remboursement_salaire_validate', methods: ['GET'])]
    public function remboursementSalaireValidate(): JsonResponse
    {
        $today = Carbon::today();
        $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatus(Status::REMBOURSEMENT, [Status::VALIDATED], $today->month, $today->year);
        $dataRemboursementValidate = [];
        foreach ($requestOperationRemboursement as $ordre => $remboursement) {
            $dataRemboursementValidate[] = [
                'ordre' => ++$ordre,
                'operation_id' => $remboursement['operation_id'],
                'date_remboursement' => $remboursement['date_operation'],
                'remboursement' => $remboursement['type_operations'],
                'matricule_salarie' => $remboursement['matricule_personal'],
                'nom_salarie' => $remboursement['name_personal'],
                'prenom_salarie' => $remboursement['lastname_personal'],
                'station_salarie' => $remboursement['stations_personal'],
                'remboursement_montant_brut' => (int)$remboursement['montant_brut'] ?? 0,
                'remboursement_montant-net' => (int)$remboursement['montant_net'] ?? 0,
                'remboursement_status' => $remboursement['status_operation']
            ];
        }
        return new JsonResponse($dataRemboursementValidate);
    }

    #[Route('/retenue_salaire', name: 'retenue_salaire', methods: ['GET'])]
    public function retenueSalaire(): JsonResponse
    {
        $today = Carbon::today();
        $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatus(Status::RETENUES, [Status::EN_ATTENTE], $today->month, $today->year);
        $dataRetenueSalaire = [];

        foreach ($requestOperationRetenues as $ordre => $retenue) {
            $dataRetenueSalaire[] = [
                'ordre' => ++$ordre,
                'operation_id' => $retenue['operation_id'],
                'date_retenue' => $retenue['date_operation'],
                'retenue' => $retenue['type_operations'],
                'matricule_salarie' => $retenue['matricule_personal'],
                'nom_salarie' => $retenue['name_personal'],
                'prenom_salarie' => $retenue['lastname_personal'],
                'station_salarie' => $retenue['stations_personal'],
                'retenue_montant_brut' => (int)$retenue['montant_brut'] ?? 0,
                'retenue_montant_net' => (int)$retenue['montant_net'] ?? 0,
                'retenue_status' => $retenue['status_operation']
            ];
        }

        return new JsonResponse($dataRetenueSalaire);
    }

    #[Route('/retenue_salaire_validate', 'retenue_salaire_validated', methods: ['GET'])]
    public function retenueSalaireValidate(): JsonResponse
    {
        $today = Carbon::today();
        $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatus(Status::RETENUES, [Status::VALIDATED], $today->month, $today->year);
        $dataRetenueSalaire = [];

        foreach ($requestOperationRetenues as $ordre => $retenue) {
            $dataRetenueSalaire[] = [
                'ordre' => ++$ordre,
                'operation_id' => $retenue['operation_id'],
                'date_retenue' => $retenue['date_operation'],
                'retenue' => $retenue['type_operations'],
                'matricule_salarie' => $retenue['matricule_personal'],
                'nom_salarie' => $retenue['name_personal'],
                'prenom_salarie' => $retenue['lastname_personal'],
                'station_salarie' => $retenue['stations_personal'],
                'retenue_montant_brut' => (int)$retenue['montant_brut'] ?? 0,
                'retenue_montant_net' => (int)$retenue['montant_net'] ?? 0,
                'retenue_status' => $retenue['status_operation']
            ];
        }

        return new JsonResponse($dataRetenueSalaire);
    }

    #[Route('/regularisation_mensuel', 'regularisation_mensuel', methods: ['GET'])]
    public function regulSalaire(): JsonResponse
    {
        $campagneActive = $this->campagneRepository->active();
        $month = (int)$campagneActive->getDateDebut()->format('m');
        $years = (int)$campagneActive->getDateDebut()->format('Y');
        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRegularisation = $this->payrollRepository->findOperationByPayroll([Status::RETENUES, Status::REMBOURSEMENT], Status::VALIDATED, $month, $years);
        } else {
            $requestOperationRegularisation = $this->payrollRepository->findOperationByPayrollByRoleEmployer([Status::RETENUES, Status::REMBOURSEMENT], Status::VALIDATED, $month, $years);
        }
        $dataRegularisation = [];

        foreach ($requestOperationRegularisation as $ordre => $regularisation) {
            $regulMoins = (int)$regularisation['remboursement_net'];
            $regulPlus = (int)$regularisation['retenue_net'];
            $dataRegularisation[] = [
                'ordre' => ++$ordre,
                'date_operations' => $regularisation['date_operation'],
                'type_operations' => $regularisation['type_operations'],
                'matricule_salarie' => $regularisation['matricule_personal'],
                'full_name_salaried' => $regularisation['name_personal'] . ' ' . $regularisation['lastname_personal'],
                'station_salarie' => $regularisation['stations_personal'],
                'regul_moins_percus' => $regulMoins,
                'regul_plus_percus' => $regulPlus,
                'net_payer_apres_regularisation' => (int)$regularisation['net_payer'] ?? 0,
                'net_payer_avant_regularisation' => (int)$regularisation['net_payer'] - $regulMoins + $regulPlus,
                'retenue_status' => $regularisation['status_operation']
            ];
        }

        return new JsonResponse($dataRegularisation);
    }

    /**
     * @throws Exception
     */
    #[Route('/regularisation_periodique', 'regularisation_periodique', methods: ['GET'])]
    public function regulSalairePeriodique(Request $request): JsonResponse
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

        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRegularisation = $this->payrollRepository->findOperationByPeriode($startAt, $endAt, $personalID);
        } else {
            $requestOperationRegularisation = $this->payrollRepository->findOperationByPeriodeByRoleEmployer($startAt, $endAt, $personalID);
        }
        $dataRegularisationPeriodique = [];

        foreach ($requestOperationRegularisation as $ordre => $regularisation) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $lastPeriode = $formatter->format($regularisation['last_campagne_date_debut']);
            $regulMoins = (int)$regularisation['remboursement_net'];
            $regulPlus = (int)$regularisation['retenue_net'];
            $dataRegularisationPeriodique[] = [
                'ordre' => ++$ordre,
                'date_operations' => $regularisation['date_operation'],
                'type_operations' => $regularisation['type_operations'],
                'matricule_salarie' => $regularisation['matricule_personal'],
                'full_name_salaried' => $regularisation['name_personal'] . ' ' . $regularisation['lastname_personal'],
                'station_salarie' => $regularisation['stations_personal'],
                'regul_moins_percus' => $regulMoins,
                'regul_plus_percus' => $regulPlus,
                'net_payer_apres_regularisation' => (int)$regularisation['net_payer'] ?? 0,
                'net_payer_avant_regularisation' => (int)$regularisation['net_payer'] - $regulMoins + $regulPlus,
                'retenue_status' => $regularisation['status_operation'],
                'periode_regulariser' => $lastPeriode
            ];
        }

        return new JsonResponse($dataRegularisationPeriodique);
    }
}