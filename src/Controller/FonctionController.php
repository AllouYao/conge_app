<?php

namespace App\Controller;

use App\Entity\Fonction;
use App\Form\FonctionType;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/fonction', name: 'fonction_')]
class FonctionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FonctionRepository $fonctionRepository;
         

    public function __construct(
        EntityManagerInterface $entityManager,
        FonctionRepository      $fonctionRepository,

    )
    {
        $this->entityManager = $entityManager;
        $this->fonctionRepository = $fonctionRepository;
    }
    #[Route('/index/api', name: 'index_api')]
    public function apiIndex(): Response
    {
        $fonctions = $this->fonctionRepository->findAll();

        $fonctionData = [];
        $index = 0;
        foreach ($fonctions as $fonction) {
            $link = $this->generateUrl('fonction_edit', ['uuid' => $fonction->getUuid()]);

            $fonctionData[] = [
                'index' => ++$index,
                'code' => $fonction->getCode(),
                'libelle' => $fonction->getCode(),
                'modifier' => $link 
            ];
        }
        return new JsonResponse($fonctionData);
    }

    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        $this->fonctionRepository->findAll();
        return $this->render('fonction/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $newFonction = new Fonction();
        $form = $this->createForm(FonctionType::class, $newFonction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newFonction);
            $this->entityManager->flush();
            flash()->addSuccess('Fonction Ajoutée avec succès.');
            return $this->redirectToRoute('fonction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fonction/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Fonction $category, Request $request): Response
    {
        $form = $this->createForm(FonctionType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            flash()->addSuccess('Fonction modifiée avec succès.');
            return $this->redirectToRoute('fonction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fonction/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
