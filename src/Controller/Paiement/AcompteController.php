<?php

namespace App\Controller\Paiement;

use App\Entity\DevPaie\Operation;
use App\Entity\User;
use App\Form\Paiement\AcompteType;
use App\Repository\DevPaie\OperationRepository;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paiement/acompte', name: 'app_paiement_acompte_')]
class AcompteController extends AbstractController
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('paiement/acompte/list.html.twig', [
            'date' => $date
        ]);
    }

    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        return $this->render('paiement/acompte/index.html.twig');
    }

    #[Route('/validated', name: 'validated')]
    public function validated(): Response
    {
        return $this->render('paiement/acompte/validated.html.twig');
    }

    #[Route('/api/acompte', name: 'api_acompte')]
    public function apiAcompte(OperationRepository $operationRepository): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $operations = $operationRepository->findOperationByType([Status::ACOMPTE, Status::PRET], $today->month, $today->year);
        } else {
            $operations = $operationRepository->findOperationByTypeAndEmployerRole([Status::ACOMPTE, Status::PRET], $today->month, $today->year);
        }
        $data = [];

        foreach ($operations as $operation) {
            $data[] = [
                'id' => $operation->getId(),
                'date' => date_format($operation->getDateOperation(), 'd/m/Y'),
                'matricule' => $operation->getPersonal()->getMatricule(),
                'nom_prenom' => $operation->getPersonal()->getFirstName() . ' ' . $operation->getPersonal()->getLastName(),
                'genre' => $operation->getPersonal()->getGenre(),
                'type' => $operation->getTypeOperations(),
                'amount' => $operation->getAmount(),
                'amount_refund' => $operation->getAmountRefund() ?? 0,
                'remaining' => $operation->getRemaining(),
                'status' => $operation->getStatus(),
                'modifier' => $this->generateUrl('app_paiement_acompte_edit', ['uuid' => $operation->getUuid()])
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/api/pending', name: 'api_pending')]
    public function apiAcomptePending(OperationRepository $operationRepository): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $operations = $operationRepository->findAcomptAndPretByStatus([Status::ACOMPTE, Status::PRET], Status::EN_ATTENTE, $today->month, $today->year);
        } else {
            $operations = $operationRepository->findAcomptAndPretByStatusAndEmployerRole([Status::ACOMPTE, Status::PRET], Status::EN_ATTENTE, $today->month, $today->year);
        }
        $data = [];

        foreach ($operations as $operation) {
            $data[] = [
                'id' => $operation->getId(),
                'date' => date_format($operation->getDateOperation(), 'd/m/Y'),
                'matricule' => $operation->getPersonal()->getMatricule(),
                'nom_prenom' => $operation->getPersonal()->getFirstName() . ' ' . $operation->getPersonal()->getLastName(),
                'genre' => $operation->getPersonal()->getGenre(),
                'type' => $operation->getTypeOperations(),
                'amount' => (int)$operation->getAmount(),
                'amount_refund' => (int)$operation->getAmountRefund() ?? 0,
                'remaining' => (int)$operation->getRemaining(),
                'status' => $operation->getStatus(),
                'modifier' => $this->generateUrl('app_paiement_acompte_edit', ['uuid' => $operation->getUuid()])
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/api/validate', name: 'api_validate')]
    public function apiValidate(OperationRepository $operationRepository): JsonResponse
    {
        $today = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $operations = $operationRepository->findAcomptAndPretByStatus([Status::ACOMPTE, Status::PRET], Status::VALIDATED, $today->month, $today->year);
        } else {
            $operations = $operationRepository->findAcomptAndPretByStatusAndEmployerRole([Status::ACOMPTE, Status::PRET], Status::VALIDATED, $today->month, $today->year);
        }
        $data = [];

        foreach ($operations as $operation) {
            $data[] = [
                'date' => date_format($operation->getDateOperation(), 'd/m/Y'),
                'matricule' => $operation->getPersonal()->getMatricule(),
                'nom_prenom' => $operation->getPersonal()->getFirstName() . ' ' . $operation->getPersonal()->getLastName(),
                'type' => $operation->getTypeOperations(),
                'amount' => (int)$operation->getAmount(),
                'amount_refund' => (int)$operation->getAmountRefund() ?? 0,
                'remaining' => (int)$operation->getRemaining(),
                'status' => $operation->getStatus(),
                'modifier' => $this->generateUrl('app_paiement_acompte_edit', ['uuid' => $operation->getUuid()])
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request, OperationRepository $operationRepository): Response
    {
        if ($request->request->has('acompteInput') && $request->isMethod('POST')) {

            $acompteInput = $request->request->get('acompteInput');
            $acomptes = json_decode($acompteInput);
            if ($acomptes) {
                foreach ($acomptes as $acompteId) {
                    $acompte = $operationRepository->findOneBy(['id' => $acompteId]);
                    if ($acompte) {
                        $acompte->setStatus(Status::VALIDATED);
                        $this->manager->persist($acompte);
                    }
                }
                $this->manager->flush();
                flash()->addSuccess('Opération validée avec succès!');
                return $this->redirectToRoute('app_paiement_acompte_index');
            } else {
                flash()->addWarning('Aucune opération sélectionnée');
                return $this->redirectToRoute('app_paiement_acompte_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->redirectToRoute('app_paiement_acompte_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();

        $operation = new Operation();
        $form = $this->createForm(AcompteType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $form->get('personal')->getData();
            $operation->setUser($currentUser)
                ->setStatus(Status::EN_ATTENTE)
                ->setPersonal($personal)
                ->setAmountRefund(0)
                ->setRemaining($operation->getAmount());
            $this->manager->persist($operation);
            $this->manager->flush();
            flash()->addSuccess($operation->getTypeOperations() . ' enregistré avec succès');
            return $this->redirectToRoute('app_paiement_acompte_list');
        }
        return $this->render('paiement/acompte/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Operation $operation, Request $request): Response
    {
        $form = $this->createForm(AcompteType::class, $operation);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($operation);
            $this->manager->flush();
        }
        return $this->render('paiement/acompte/edit.html.twig', [
            'form' => $form
        ]);
    }
}
