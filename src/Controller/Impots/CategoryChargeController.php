<?php

namespace App\Controller\Impots;

use App\Entity\Impots\CategoryCharge;
use App\Form\Impots\CategoryChargeType;
use App\Repository\Impots\CategoryChargeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/impots/category/charge', name: 'import_category_charge_')]
class CategoryChargeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CategoryChargeRepository $categoryChargeRepository): Response
    {
        return $this->render('impots/category_charge/index.html.twig', [
            'category_charges' => $categoryChargeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoryCharge = new CategoryCharge();
        $form = $this->createForm(CategoryChargeType::class, $categoryCharge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categoryCharge);
            $entityManager->flush();
            flash()->addSuccess('Catégorie de charge ajouter avec succès.');
            return $this->redirectToRoute('import_category_charge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('impots/category_charge/new.html.twig', [
            'category_charge' => $categoryCharge,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategoryCharge $categoryCharge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryChargeType::class, $categoryCharge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Catégorie de charge modifier avec succès.');
            return $this->redirectToRoute('import_category_charge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('impots/category_charge/edit.html.twig', [
            'category_charge' => $categoryCharge,
            'form' => $form,
        ]);
    }
}
