<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Form\DossierPersonal\DepartureType;
use App\Repository\DossierPersonal\DepartureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/departure', name: 'departure_')]
class DepartureController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DepartureRepository $departureRepository): Response
    {
        return $this->render('dossier_personal/departure/index.html.twig', [
            'departures' => $departureRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departure = new Departure();
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($departure);
            $entityManager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/show/{uuid}', name: 'show', methods: ['GET'])]
    public function show(Departure $departure): Response
    {
        return $this->render('dossier_personal/departure/show.html.twig', [
            'departure' => $departure,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departure $departure, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            flash()->addSuccess('Départ modifier avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/edit.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }
}
