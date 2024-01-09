<?php

namespace App\Controller\Settings;

use App\Entity\Settings\Smig;
use App\Form\Settings\SmigType;
use App\Repository\Settings\SmigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings/smig', name: 'settings_smig_')]
class SmigController extends AbstractController
{
    #[Route('/api_smig', name: 'api_smig', methods: ['GET'])]
    public function apiSmig(SmigRepository $smigRepository): JsonResponse
    {
        $smig = $smigRepository->findBy(['id' => 1], null, 1);
        $apiSmig = [];

        foreach ($smig as $item) {
            $apiSmig[] = [
                'date_debut' => date_format($item->getDateDebut(), 'd/m/Y'),
                'date_fin' => date_format($item->getDateFin(), 'd/m/Y'),
                'amount' => $item->getAmount(),
                'active' => $item->isIsActive() ? 'OUI' : 'NOM',
                'modifier' => $this->generateUrl('settings_smig_edit', ['uuid' => $item->getUuid()])
            ];
        }

        return new JsonResponse($apiSmig);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/smig/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $smig = new Smig();
        $form = $this->createForm(SmigType::class, $smig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($smig);
            $entityManager->flush();
            flash()->addSuccess('Smig ajouté avec succès');
            return $this->redirectToRoute('settings_smig_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/smig/new.html.twig', [
            'smig' => $smig,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Smig $smig, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SmigType::class, $smig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Smig modifié avec succès');
            return $this->redirectToRoute('settings_smig_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/smig/edit.html.twig', [
            'smig' => $smig,
            'form' => $form,
        ]);
    }

}
