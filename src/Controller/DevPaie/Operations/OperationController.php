<?php

namespace App\Controller\DevPaie\Operations;

use App\Entity\DevPaie\Operation;
use App\Entity\User;
use App\Form\DevPaie\OperationType;
use App\Repository\DevPaie\OperationRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dev/paie/operation', name: 'dev_paie_operation_')]
class OperationController extends AbstractController
{
    public function __construct(
        private readonly OperationRepository $operationRepository,
        private readonly CampagneRepository  $campagneRepository
    )
    {
    }

    #[Route('/api_operation', name: 'api_operation', methods: ['GET'])]
    public function apiOperations(OperationRepository $operationRepository): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $operationRequest = $operationRepository->findOperationByType([Status::REMBOURSEMENT, Status::RETENUES], $today->month, $today->year);
        } else {
            $operationRequest = $operationRepository->findOperationByTypeAndEmployerRole([Status::REMBOURSEMENT, Status::RETENUES], $today->month, $today->year);
        }
        if (!$operationRequest) {
            return $this->json(['data' => []]);
        }
        $apiOperation = [];
        foreach ($operationRequest as $operation) {
            $personal = $operation->getPersonal();
            $apiOperation[] = [
                'date_operation' => date_format($operation->getDateOperation(), 'd/m/Y'),
                'type_operation' => $operation->getTypeOperations(),
                'personal_matricule' => $personal->getMatricule(),
                'personal_fullname' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                'amount_brut' => $operation->getAmountBrut(),
                'amount_net' => $operation->getAmountNet(),
                'status' => $operation->getStatus(),
                'modifier' => $this->generateUrl('dev_paie_operation_edit', ['uuid' => $operation->getUuid()])
            ];
        }
        return new JsonResponse($apiOperation);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dev_paie/operation/index.html.twig', [
            'date' => $date
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        $lastCampagne = $this->campagneRepository->lastCampagne(true);
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $operation->setUser($currentUser)->setStatus(Status::EN_ATTENTE)->setCampagne($lastCampagne);
            $entityManager->persist($operation);
            $entityManager->flush();
            flash()->addSuccess('Opération de ' . $operation->getTypeOperations() . ' enregistrer avec succès');
            return $this->redirectToRoute('dev_paie_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dev_paie/operation/new.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/validates', name: 'show_validate', methods: ['POST', 'GET'])]
    public function show(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('remboursementValidation') && $request->isMethod('POST')) {
            $remboursementInput = $request->request->get('remboursementValidation');
            if ($remboursementInput != "[]") {
                $remboursements = json_decode($remboursementInput);
                foreach ($remboursements as $remboursementId) {
                    $remboursement = $this->operationRepository->findOneBy(['id' => $remboursementId]);
                    if ($remboursement) {
                        if ($remboursement->getStatus() === Status::EN_ATTENTE) {
                            $remboursement->setStatus(Status::VALIDATED);
                            $entityManager->persist($remboursement);
                            $entityManager->flush();
                        }
                    }
                }
                flash()->addSuccess('Remboursement validé avec succès!');
            } else {
                flash()->addWarning('Aucun remboursement en attente de validation sélectionner !');
                return $this->redirectToRoute('reporting_paie_remboursement_salaires');
            }
        }
        return $this->redirectToRoute('reporting_paie_remboursement_salaires');
    }

    #[Route('/validates_retenue', name: 'validate_retenue', methods: ['POST', 'GET'])]
    public function validateRetenue(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('retenueSalaireValidation') && $request->isMethod('POST')) {
            $retenuesInput = $request->request->get('retenueSalaireValidation');
            if ($retenuesInput != "[]") {
                $retenuesSalaires = json_decode($retenuesInput);
                foreach ($retenuesSalaires as $retenueId) {
                    $retenues = $this->operationRepository->findOneBy(['id' => $retenueId]);
                    if ($retenues) {
                        if ($retenues->getStatus() === Status::EN_ATTENTE) {
                            $retenues->setStatus(Status::VALIDATED);
                            $entityManager->persist($retenues);
                            $entityManager->flush();
                        }
                    }
                }
                flash()->addSuccess('Retenue sur salaire validé avec succès!');
            } else {
                flash()->addWarning('Aucune retenue sur salaire en attente sélectionnées !');
                return $this->redirectToRoute('reporting_paie_retenue_salaires');
            }

        }
        return $this->redirectToRoute('reporting_paie_retenue_salaires');
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);
        $lastCampagne = $this->campagneRepository->lastCampagne(true);
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $operation->setUser($currentUser)->setStatus(Status::EN_ATTENTE)->setCampagne($lastCampagne);
            $entityManager->flush();
            flash()->addSuccess('Opération de ' . $operation->getTypeOperations() . ' modifier avec succès');
            return $this->redirectToRoute('dev_paie_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dev_paie/operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $operation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dev_paie_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}
