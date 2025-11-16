<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Fonction;
use App\Form\ServiceType;
use App\Form\FonctionType;
use App\Repository\ServiceRepository;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/service', name: 'service_')]
class ServiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceRepository      $serviceRepository,

    )
    {
    }
    #[Route('/index/api', name: 'index_api')]
    public function apiIndex(): Response
    {
        $services = $this->serviceRepository->findAll();

        $serviceData = [];
        $index = 0;
        foreach ($services as $service) {
            $link = $this->generateUrl('service_edit', ['uuid' => $service->getUuid()]);

            $serviceData[] = [
                'index' => ++$index,
                'code' => $service->getCode(),
                'libelle' => $service->getLibelle(),
                'modifier' => $link 
            ];
        }
        return new JsonResponse($serviceData);
    }

    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        $this->serviceRepository->findAll();
        return $this->render('service/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $newService = new Service();
        $form = $this->createForm(ServiceType::class, $newService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($newService);
            $this->entityManager->flush();
            flash()->addSuccess('Service Ajouté avec succès.');
            return $this->redirectToRoute('service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Service $service, Request $request): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($service);
            $this->entityManager->flush();
            flash()->addSuccess('Service modifié avec succès.');
            return $this->redirectToRoute('service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('service/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
