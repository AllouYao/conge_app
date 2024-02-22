<?php

namespace App\Controller\Impots;

use App\Entity\Impots\CategoryCharge;
use App\Form\Impots\CategoryChargeType;
use App\Repository\Impots\CategoryChargeRepository;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/impots/category/charge', name: 'impot_category_charge_')]
class CategoryChargeController extends AbstractController
{
    #[Route('/api_categorie_charge_fiscal', name: 'api_categorie_charge_fiscal', methods: ['GET'])]
    public function apiCategorieChargeFiscale(CategoryChargeRepository $categoryChargeRepository): JsonResponse
    {
        $categoryCharge = $categoryChargeRepository->findBy(['typeCharge' => Status::FISCALE_CHARGE]);
        $charge = [];
        foreach ($categoryCharge as $index => $item) {
            $charge[] = [
                'type_charge' => $item->getTypeCharge(),
                'categorie' => $item->getCategory(),
                'intitule' => $item->getIntitule(),
                'codification' => $item->getCodification(),
                'taux' => $item->getValue(),
                'description' => $item->getDescription(),
                'date_creation' => date_format($item->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('impot_category_charge_edit', ['uuid' => $item->getUuid()])
            ];
        }
        return new JsonResponse($charge);
    }

    #[Route('/api_categorie_charge_sociale', name: 'api_categorie_charge_sociale', methods: ['GET'])]
    public function apiCategorieChargeSociale(CategoryChargeRepository $categoryChargeRepository): JsonResponse
    {
        $categoryCharge = $categoryChargeRepository->findBy(['typeCharge' => Status::SOCIALE_CHARGE]);
        $charge = [];
        foreach ($categoryCharge as $index => $item) {
            $charge[] = [
                'type_charge' => $item->getTypeCharge(),
                'categorie' => $item->getCategory(),
                'intitule' => $item->getIntitule(),
                'codification' => $item->getCodification(),
                'taux' => $item->getValue(),
                'description' => $item->getDescription(),
                'date_creation' => date_format($item->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('impot_category_charge_edit', ['uuid' => $item->getUuid()])
            ];
        }
        return new JsonResponse($charge);
    }

    #[Route('/fixcale', name: 'index_fixcale', methods: ['GET'])]
    public function indexFixcale(): Response
    {
        return $this->render('impots/category_charge/fiscale.html.twig');
    }
    #[Route('/sociale', name: 'index_sociale', methods: ['GET'])]
    public function indexSociale(): Response
    {
        return $this->render('impots/category_charge/sociale.html.twig');
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
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
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
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('impots/category_charge/edit.html.twig', [
            'category_charge' => $categoryCharge,
            'form' => $form,
        ]);
    }
}
