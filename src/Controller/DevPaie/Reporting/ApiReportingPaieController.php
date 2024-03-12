<?php

namespace App\Controller\DevPaie\Reporting;

use App\Repository\DevPaie\OperationRepository;
use App\Utils\Status;
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
        $requestOperationRemboursement = $this->operationRepository->findOperationByTypeAndStatus(Status::REMBOURSEMENT, [Status::EN_ATTENTE, Status::VALIDATED]);
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
}