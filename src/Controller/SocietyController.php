<?php

namespace App\Controller;

use App\Entity\Society;
use App\Form\SocietyType;
use App\Repository\SocietyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/society', name: 'admin_society_')]
class SocietyController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SocietyRepository $societyRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SocietyRepository      $societyRepository,
    )
    {
        $this->entityManager = $entityManager;
        $this->societyRepository = $societyRepository;

    }

    #[Route('/api_societe', name: 'api_index', methods: ['GET'])]
    public function apiSociete(): JsonResponse
    {
        $society = $this->societyRepository->getFirstResult();
        $url = $this->generateUrl('admin_society_edit', ['uuid' => $society?->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);
        $apiSociety = [
            'raison_social' => $society?->getRaisonSocial(),
            'forme' => $society?->getForme(),
            'activity' => $society?->getActivity(),
            'numero_cc' => $society?->getNumeroCc(),
            'telephone' => $society?->getTelephone(),
            'siege' => $society?->getSiege(),
            'modifier' => $url
        ];

        return new JsonResponse($apiSociety);
    }

    #[Route('/show', name: 'show')]
    public function show(): Response
    {
        return $this->render('admin/society/show.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $society = $this->societyRepository->getFirstResult();

        if ($society) {
            flash()->addWarning('Une société a été déjà créer !');
            return $this->redirectToRoute('admin_society_show', [], Response::HTTP_SEE_OTHER);
        }

        $newSociety = new Society();
        $form = $this->createForm(SocietyType::class, $newSociety);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newSociety);
            $this->entityManager->flush();
            flash()->addSuccess('Société modifié avec succès.');
            return $this->redirectToRoute('admin_society_show', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/society/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Society $society, Request $request): Response
    {
        $form = $this->createForm(SocietyType::class, $society);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($society);
            $this->entityManager->flush();
            flash()->addSuccess('Société modifié avec succès.');
            return $this->redirectToRoute('admin_society_show', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/society/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}