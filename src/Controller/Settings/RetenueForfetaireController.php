<?php

namespace App\Controller\Settings;

use App\Entity\DossierPersonal\RetenueForfetaire;
use App\Form\Settings\RetenueForfetaireType;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/retenue_forfetaire', name: 'settings_retenue_forfetaire_')]
class RetenueForfetaireController extends AbstractController
{
    #[Route('/api_forfetaire', name: 'api_forfetaire', methods: ['GET'])]
    public function apiForfetaire(RetenueForfetaireRepository $forfetaireRepository): JsonResponse
    {
        $retenueForfetaire = $forfetaireRepository->findAll();
        $apiForfetaire = [];
        foreach ($retenueForfetaire as $forfetaire) {
            $apiForfetaire[] = [
                'intitule' => $forfetaire->getName(),
                'code' => $forfetaire->getCode(),
                'description' => $forfetaire->getDescription(),
                'valeur' => $forfetaire->getValue(),
                'date_creation' => date_format($forfetaire->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('settings_retenue_forfetaire_edit', ['uuid' => $forfetaire->getUuid()])
            ];
        }

        return new JsonResponse($apiForfetaire);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/retenue_forfetaire/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $forfetaire = new RetenueForfetaire();
        $form = $this->createForm(RetenueForfetaireType::class, $forfetaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forfetaire);
            $entityManager->flush();
            flash()->addSuccess('Prime enregistré avec succès.');
            return $this->redirectToRoute('settings_retenue_forfetaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/retenue_forfetaire/new.html.twig', [
            'forfetaire' => $forfetaire,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RetenueForfetaire $retenueForfetaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RetenueForfetaireType::class, $retenueForfetaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Prime modifier avec succès.');
            return $this->redirectToRoute('settings_retenue_forfetaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/retenue_forfetaire/edit.html.twig', [
            'forfetaire' => $retenueForfetaire,
            'form' => $form,
        ]);
    }
}