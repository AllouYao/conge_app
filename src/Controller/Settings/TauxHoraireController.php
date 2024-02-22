<?php

namespace App\Controller\Settings;

use App\Entity\Settings\TauxHoraire;
use App\Form\Settings\HoraireType;
use App\Repository\Settings\TauxHoraireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings/taux/horaire', name: 'settings_taux_horaire_')]
class TauxHoraireController extends AbstractController
{
    #[Route('/api_taux_horaire', name: 'api_taux_horaire', methods: ['GET'])]
    public function apiTauxHoraire(TauxHoraireRepository $tauxHoraireRepository): JsonResponse
    {
        $tauxHoraire = $tauxHoraireRepository->findOneBy([], ['id' => 'DESC']);
        if (!$tauxHoraire) {
            return $this->json(['data' => []]);
        }
        $apiTauxHoraire[] = [
            'date_debut' => date_format($tauxHoraire->getDateDebut(), 'd/m/Y'),
            'date_fin' => date_format($tauxHoraire->getDateFin(), 'd/m/Y'),
            'amount' => $tauxHoraire->getAmount(),
            'active' => $tauxHoraire->isIsActive() ? 'OUI' : 'NOM',
            'modifier' => $this->generateUrl('settings_taux_horaire_edit', ['uuid' => $tauxHoraire->getUuid()])
        ];

        return new JsonResponse($apiTauxHoraire);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(TauxHoraireRepository $tauxHoraireRepository): Response
    {
        $tauxHoraire = $tauxHoraireRepository->findOneBy([], ['id' => 'DESC']);
        $tauxHoraireId = $tauxHoraire?->getId();
        return $this->render('settings/taux_horaire/index.html.twig', [
            'taux_horaire' => $tauxHoraireId
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tauxHoraire = new TauxHoraire();
        $form = $this->createForm(HoraireType::class, $tauxHoraire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tauxHoraire);
            $entityManager->flush();
            flash()->addSuccess('Taux horaire ajouté avec succès.');
            return $this->redirectToRoute('settings_taux_horaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/taux_horaire/new.html.twig', [
            'taux_horaire' => $tauxHoraire,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TauxHoraire $tauxHoraire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HoraireType::class, $tauxHoraire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Taux horaire modifié avec succès.');
            return $this->redirectToRoute('settings_taux_horaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/taux_horaire/edit.html.twig', [
            'taux_horaire' => $tauxHoraire,
            'form' => $form,
        ]);
    }

}
