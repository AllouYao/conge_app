<?php

namespace App\Controller\Settings;

use App\Entity\Settings\Primes;
use App\Form\Settings\PrimesType;
use App\Repository\Settings\PrimesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings/primes', name: 'settings_prime_')]
class PrimesController extends AbstractController
{
    #[Route('/api_primes/', name: 'api_primes', methods: ['GET'])]
    public function apiPrimes(PrimesRepository $primesRepository): JsonResponse
    {
        $primes = $primesRepository->findAll();
        $apiPrimes = [];
        foreach ($primes as $prime) {
            $apiPrimes[] = [
                'intitule' => $prime->getIntitule(),
                'code' => $prime->getCode(),
                'description' => $prime->getDescription(),
                'valeur' => $prime->getTaux(),
                'date_creation' => date_format($prime->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('settings_prime_edit', ['uuid' => $prime->getUuid()])
            ];
        }

        return new JsonResponse($apiPrimes);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/primes/index.html.twig');
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
}
