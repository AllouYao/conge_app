<?php

namespace App\Controller;

use App\Entity\TypeConge;
use App\Form\TypeCongeType;
use App\Repository\TypeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/type-conge', name: 'type_conge_')]
class TypeCongeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TypeCongeRepository $typeCongeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TypeCongeRepository $typeCongeRepository,
    )
    {
        $this->entityManager = $entityManager;
        $this->typeCongeRepository = $typeCongeRepository;
    }

    #[Route('/index/api', name: 'index_api', methods: ['GET'])]
    public function apiIndex(): JsonResponse
    {
        $typeConges = $this->typeCongeRepository->findAll();
        $typeCongeData = [];
        $index = 0;
        foreach ($typeConges as $typeConge) {
            $link = $this->generateUrl('type_conge_edit', ['uuid' => $typeConge->getUuid()]);

            $typeCongeData[] = [
                'index' => ++$index,
                'code' => $typeConge->getCode(),
                'libelle' => $typeConge->getLibelle(),
                'modifier' => $link
            ];
        }

        return new JsonResponse($typeCongeData);
    }

    #[Route('/index', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('type_conge/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $newTypeConge = new TypeConge();
        $form = $this->createForm(TypeCongeType::class, $newTypeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newTypeConge);
            $this->entityManager->flush();
            flash()->addSuccess('Type de congé ajouté avec succès.');
            return $this->redirectToRoute('type_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_conge/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(TypeConge $typeConge, Request $request): Response
    {
        $form = $this->createForm(TypeCongeType::class, $typeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($typeConge);
            $this->entityManager->flush();
            flash()->addSuccess('Type de congé modifié avec succès.');
            return $this->redirectToRoute('type_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_conge/edit.html.twig', [
            'form' => $form->createView(),
            'typeConge' => $typeConge,
        ]);
    }
}

