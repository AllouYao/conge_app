<?php

namespace App\Controller\DevPaie;

use App\Entity\DevPaie\WorkTime;
use App\Form\DevPaie\WorkTimeNormalType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\DevPaie\WorkTimeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\DevPaie\WorkTimeSupplementaireType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/work/time', name: 'work_time_')]
class WorkTimeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private WorkTimeRepository $workTimeRepository;
    public function __construct(
        EntityManagerInterface $entityManager,
        WorkTimeRepository $workTimeRepository,
    )
    {
        $this->entityManager = $entityManager;
        $this->workTimeRepository = $workTimeRepository;

    }

    #[Route('/normal/api', name: 'normal_api', methods: ['GET'])]
    public function apiNormal(): JsonResponse
    {
        $allWorkTimes = $this->workTimeRepository->findBy(['code'=>'NORMAL']);

        $workTimes = [];

        if (!$allWorkTimes) { 
            return $this->json(['data' => []]);
        }

        foreach ($allWorkTimes as $workTime) {
            $workTimes[] = [
                'type' => $workTime->getType(),
                'hourValue' => $workTime->getHourValue(),
                'rateValue' => $workTime->getRateValue(),
                'modifier' => $this->generateUrl('work_time_normal_edit', ['uuid' => $workTime->getUuid()])
            ];  
        }
        return new JsonResponse($workTimes);
    }

    #[Route('/supplementaire/api', name: 'supplementaire_api', methods: ['GET'])]
    public function apiSupplementaire(): JsonResponse
    {
        $allWorkTimes = $this->workTimeRepository->findBy(['code'=>'SUPPLEMENTAIRE']);
        $workTimes = [];

        if (!$allWorkTimes) {
            return $this->json(['data' => []]);
        }

        foreach ($allWorkTimes as $workTime) {

            $type = null;

            if($workTime->getType() == 'MAJORATION_15_PERCENT'){
                $type = 'Majoration à 15%';

            }elseif($workTime->getType() == 'MAJORATION_50_PERCENT'){
                $type = 'Majoration à 50%';

            }elseif($workTime->getType() == 'MAJORATION_75_PERCENT'){
                $type = 'Majoration à 75%';

            }else{
                $type = 'Majoration à 100%';

            }
            $workTimes[] = [
                'type' => $type,
                'hourValue' => $workTime->getHourValue(),
                'rateValue' => $workTime->getRateValue(),
                'modifier' => $this->generateUrl('work_time_supplementaire_edit', ['uuid' => $workTime->getUuid()])
            ];
        }
        return new JsonResponse($workTimes);
    }


    #[Route('/normal', name: 'normal_index')]
    public function indexNormal(): Response
    {

        return $this->render('dev_paie/work_time/normal/index.html.twig');
    }
    #[Route('/supplementaire', name: 'supplementaire_index')]
    public function indexSupplementaire(): Response
    {

        return $this->render('dev_paie/work_time/supplementaire/index.html.twig');
    }



    #[Route('/normal/new', name: 'normal_new', methods: ['GET', 'POST'])]
    public function newNormal(Request $request): Response
    {
        $workTime = new WorkTime();
        $form = $this->createForm(WorkTimeNormalType::class,$workTime);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($workTime);
            $this->entityManager->flush();

            flash()->addSuccess('Temps de travail normal créer avec succès.');
            return $this->redirectToRoute('work_time_normal_index', [], Response::HTTP_SEE_OTHER);
    }
 
        return $this->render('dev_paie/work_time/normal/new.html.twig', [
            'form' => $form
        ]);
    }
    #[Route('/supplementaire/new', name: 'supplementaire_new', methods: ['GET', 'POST'])]
    public function newSupplementaire(Request $request): Response
    {
        $workTime = new WorkTime();
        $form = $this->createForm(WorkTimeSupplementaireType::class,$workTime);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($workTime);
            $this->entityManager->flush();

            flash()->addSuccess('Temps de travail créer avec succès.');
            return $this->redirectToRoute('work_time_supplementaire_index', [], Response::HTTP_SEE_OTHER);
    }
 
        return $this->render('dev_paie/work_time/supplementaire/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/normal/edit', name: 'normal_edit', methods: ['GET', 'POST'])]
    public function edit(WorkTime $WorkTime, Request $request): Response
    {
        $form = $this->createForm(WorkTimeNormalType::class, $WorkTime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->entityManager->persist($WorkTime); 
            $this->entityManager->flush();
            flash()->addSuccess('Temps de travail modifié avec succès.');
            return $this->redirectToRoute('work_time_normal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dev_paie/work_time/normal/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/supplementaire/edit', name: 'supplementaire_edit', methods: ['GET', 'POST'])]
    public function editSupplementaire(WorkTime $workTime, Request $request): Response
    {
        $form = $this->createForm(WorkTimeSupplementaireType::class, $workTime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->entityManager->persist($workTime); 
            $this->entityManager->flush();
            flash()->addSuccess('Temps de travail supplementaire modifié avec succès.');
            return $this->redirectToRoute('work_time_supplementaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dev_paie/work_time/supplementaire/edit.html.twig', [
            'form' => $form,
        ]);
    }



}