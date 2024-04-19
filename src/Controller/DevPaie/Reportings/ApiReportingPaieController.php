<?php

namespace App\Controller\DevPaie\Reportings;

use App\Repository\DevPaie\OperationRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api_reporting_paie', name: 'api_reporting_paie_', methods: ['GET'])]
class ApiReportingPaieController extends AbstractController
{
    public function __construct(
        private readonly OperationRepository $operationRepository
    )
    {
    }

    #[Route('/remboursement_salaire', name: 'remboursement_salaire', methods: ['GET'])]
    public function remboursementSalaire(): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatus(Status::REMBOURSEMENT, [Status::EN_ATTENTE], $today->month, $today->year);
        } else {
            $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatusByEmployerRole(Status::REMBOURSEMENT, [Status::EN_ATTENTE], $today->month, $today->year);
        }
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
                'remboursement_montant_net' => (int)$remboursement['montant_net'] ?? 0,
                'remboursement_status' => $remboursement['status_operation']
            ];
        }

        return new JsonResponse($dataRemboursement);
    }

    #[Route('/remboursement_salaire_validate', name: 'remboursement_salaire_validate', methods: ['GET'])]
    public function remboursementSalaireValidate(): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatus(Status::REMBOURSEMENT, [Status::VALIDATED], $today->month, $today->year);
        } else {
            $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatusByEmployerRole(Status::REMBOURSEMENT, [Status::VALIDATED], $today->month, $today->year);
        }
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
                'remboursement_montant_net' => (int)$remboursement['montant_net'] ?? 0,
                'remboursement_status' => $remboursement['status_operation']
            ];
        }
        return new JsonResponse($dataRemboursementValidate);
    }

    #[Route('/retenue_salaire', name: 'retenue_salaire', methods: ['GET'])]
    public function retenueSalaire(): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatus(Status::RETENUES, [Status::EN_ATTENTE], $today->month, $today->year);
        } else {
            $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatusByEmployerRole(Status::RETENUES, [Status::EN_ATTENTE], $today->month, $today->year);
        }
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
        if ($this->isGranted('ROLE_RH')) {
            $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatus(Status::RETENUES, [Status::VALIDATED], $today->month, $today->year);
        } else {
            $requestOperationRetenues = $this->operationRepository->findOperationByTypeAndStatusByEmployerRole(Status::RETENUES, [Status::VALIDATED], $today->month, $today->year);
        }
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
}