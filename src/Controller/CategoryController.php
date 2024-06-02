<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/category', name: 'category_')]
class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
         

    public function __construct(
        EntityManagerInterface $entityManager,
        CategoryRepository      $categoryRepository,

    )
    {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('/index/api', name: 'index_api')]
    public function apiIndex(): Response
    {
        $categories = $this->categoryRepository->findAll();
        $categoryData = [];
        $index = 0;
        foreach ($categories as $category) {
            $link = $this->generateUrl('category_edit', ['uuid' => $category->getUuid()]);

            $categoryData[] = [
                'index' => ++$index,
                'code' => $category->getCode(),
                'libelle' => $category->getCode(),
                'modifier' => $link 
            ];
        }

        return new JsonResponse($categoryData);
    }
    
    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $newCategory = new Category();
        $form = $this->createForm(CategoryType::class, $newCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newCategory);
            $this->entityManager->flush();
            flash()->addSuccess('Categorie Ajoutée avec succès.');
            return $this->redirectToRoute('category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            flash()->addSuccess('Catégorie modifiée avec succès.');
            return $this->redirectToRoute('category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
