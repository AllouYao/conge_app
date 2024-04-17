<?php

namespace App\Controller\Settings;

use App\Entity\Settings\CategorySalarie;
use App\Form\Settings\CategorySalarieType;
use App\Repository\Settings\CategorySalarieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/category/salarie', name: 'settings_category_salarie_')]
class CategorySalarieController extends AbstractController
{
    #[Route('/api_categorySalaire', name: 'api_categorySalaire', methods: ['GET'])]
    public function api_categorySalaire(CategorySalarieRepository $categorySalarieRepository): JsonResponse
    {
        $categorySalaries = $categorySalarieRepository->findAll();
        $categoriesSalaried = [];

        foreach ($categorySalaries as $item) {
            $categoriesSalaried[] = [
                'name' => $item->getName(),
                'code' => $item->getCode(),
                'description' => $item->getDescription(),
                'date_creation' => date_format($item->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('settings_category_salarie_edit', ['uuid' => $item->getUuid()])
            ];
        }
        return new JsonResponse($categoriesSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/category_salarie/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorySalarie = new CategorySalarie();
        $form = $this->createForm(CategorySalarieType::class, $categorySalarie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorySalarie);
            $entityManager->flush();
            flash()->addSuccess('Catégorie de salaire ajouté avec succès.');
            return $this->redirectToRoute('settings_category_salarie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/category_salarie/new.html.twig', [
            'category_salarie' => $categorySalarie,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorySalarie $categorySalarie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorySalarieType::class, $categorySalarie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Catégorie de salaire modifié avec succès.');
            return $this->redirectToRoute('settings_category_salarie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/category_salarie/edit.html.twig', [
            'category_salarie' => $categorySalarie,
            'form' => $form,
        ]);
    }
}
