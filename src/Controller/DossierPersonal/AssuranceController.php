<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\User;
use App\Form\DossierPersonal\AssuranceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assurance', name: 'personal_assurance_')]
class AssuranceController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        $form = $this->createForm(AssuranceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assurances = $form->get('detailRetenueForfetaires')->getData();
            $personal = $form->get('personal')->getData();
            foreach ($assurances as $assurance) {
                $assurance->setPersonal($personal);
                $assurance->setUser($currentUser);
                $manager->persist($assurance);
            }
            $manager->flush();
            flash()->addSuccess('Assurance santé ajouter avec succès.');
            return $this->redirectToRoute('');
        }

        return $this->render('dossier_personal/assurance/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(DetailRetenueForfetaire $assurances, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AssuranceType::class, [
            'personal' => $assurances->getPersonal(),
            'detailRetenueForfetaires' => $assurances->getPersonal()->getDetailRetenueForfetaires()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($assurances as $assurance) {
                $assurance->setPersonal($assurance->getPersonal());
                $manager->persist($assurance);
            }
            $manager->flush();
            flash()->addSuccess('Assurance modifée avec succès.');
            return $this->redirectToRoute('', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/assurance/edit.html.twig', [
            'personal' => $assurances->getPersonal(),
            'form' => $form,
            'editing' => true
        ]);
    }
}