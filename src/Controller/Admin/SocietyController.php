<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Society;
use App\Entity\User;
use App\Form\Admin\SocietyType;
use App\Repository\Admin\SocietyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/society', name: 'admin_society_')]
class SocietyController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SocietyRepository $societyRepository;
    public function __construct(
        EntityManagerInterface $entityManager,
        SocietyRepository $societyRepository,
    )
    {
        $this->entityManager = $entityManager;
        $this->societyRepository = $societyRepository;

    }

    #[Route('/show', name: 'show')]
    public function show(): Response
    {
        $society= $this->societyRepository->getFirstResult();

        //dd($society);

        return $this->render('admin/society/show.html.twig', [
            'society' => $society,
        ]);
    }
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $society= $this->societyRepository->getFirstResult();

        if($society){

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
            'form' => $form,
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
            'form' => $form,
        ]);
    }

}