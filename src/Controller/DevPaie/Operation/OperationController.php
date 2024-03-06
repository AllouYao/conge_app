<?php

namespace App\Controller\DevPaie\Operation;

use App\Entity\DevPaie\Operation;
use App\Entity\User;
use App\Form\DevPaie\OperationType;
use App\Repository\DevPaie\OperationRepository;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev/paie/operation', name: 'dev_paie_operation_')]
class OperationController extends AbstractController
{
    #[Route('/api_operation', name: 'api_operation', methods: ['GET'])]
    public function apiOperations(OperationRepository $operationRepository): JsonResponse
    {
        $operationRequest = $operationRepository->findOperationByType([Status::REMBOURSEMENT, Status::RETENUES]);
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
                'modifier' => $this->generateUrl('dev_paie_operation_edit', ['uuid' => $operation->getUuid()])
            ];
        }
        return new JsonResponse($apiOperation);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_DEV_PAIE', message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    public function index(): Response
    {
        return $this->render('dev_paie/operation/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_DEV_PAIE', message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $operation->setUser($currentUser);
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

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('dev_paie/operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_DEV_PAIE', message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            $operation->setUser($currentUser);
            $entityManager->flush();
            flash()->addSuccess('Opération de ' . $operation->getTypeOperations() . ' modifier avec succès');
            return $this->redirectToRoute('dev_paie_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dev_paie/operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $operation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dev_paie_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}
