<?php

namespace App\Controller\Settings;

use App\Entity\Settings\Category;
use App\Form\Settings\CategoryType;
use App\Repository\Settings\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Flasher\Prime\FlasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/category', name: 'settings_category_')]
class CategoryController extends AbstractController
{
    #[Route('/api_categorie', name: 'api_category', methods: ['GET'])]
    public function apiCategory(CategoryRepository $categoryRepository): JsonResponse
    {
        $category = $categoryRepository->findCategorie();
        $apiCategorie = [];
        foreach ($category as $item) {
            $apiCategorie[] = [
                'intitule' => $item->getCategorySalarie()->getName(),
                'categorie' => $item->getIntitule(),
                'amount' => $item->getAmount(),
                'date_creation' => date_format($item->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('settings_category_edit', ['uuid' => $item->getUuid()])
            ];
        }
        return new JsonResponse($apiCategorie);
    }

    #[IsGranted("ROLE_DEV_PAIE", message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/category/index.html.twig');
    }

    #[IsGranted("ROLE_DEV_PAIE", message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FlasherInterface $flasher): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $flasher->addSuccess('Catégorie salariale crée avec succès.');
            return $this->redirectToRoute('settings_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[IsGranted("ROLE_DEV_PAIE", message: 'Vous avez pas les accès, veillez quitter la page. merci!', statusCode: 404)]
    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addFlash('success', 'Catégorie salariale modifiée avec succès.');
            return $this->redirectToRoute('settings_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }
}
