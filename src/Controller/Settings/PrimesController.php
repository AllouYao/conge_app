<?php

namespace App\Controller\Settings;

use App\Entity\Settings\Primes;
use App\Form\Settings\PrimesType;
use App\Repository\Settings\PrimesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings/primes', name: 'settings_prime_')]
class PrimesController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PrimesRepository $primesRepository): Response
    {
        return $this->render('settings/primes/index.html.twig', [
            'primes' => $primesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $prime = new Primes();
        $form = $this->createForm(PrimesType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($prime);
            $entityManager->flush();
            flash()->addSuccess('Prime enregistré avec succès.');
            return $this->redirectToRoute('settings_prime_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/primes/new.html.twig', [
            'prime' => $prime,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/show', name: 'show', methods: ['GET'])]
    public function show(Primes $prime): Response
    {
        return $this->render('settings/primes/show.html.twig', [
            'prime' => $prime,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Primes $prime, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PrimesType::class, $prime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Prime modifier avec succès.');
            return $this->redirectToRoute('settings_prime_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('settings/primes/edit.html.twig', [
            'prime' => $prime,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Primes $prime, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$prime->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prime);
            $entityManager->flush();
        }

        return $this->redirectToRoute('settings_prime_index', [], Response::HTTP_SEE_OTHER);
    }
}
