<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\ChargeType;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\SalaryImpotsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/charge_people', name: 'charge_people_')]
class ChargePeopleController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(PersonalRepository $personalRepository): Response
    {
        return $this->render('dossier_personal/charge_people/index.html.twig', [
            'personals' => $personalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $manager,
        SalaryImpotsService    $salary
    ): Response
    {
        $form = $this->createForm(ChargeType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $chargePeople = $form->get('chargePeople')->getData();
            $personal = $form->get('personal')->getData();
            foreach ($chargePeople as $chargePerson) {
                $chargePerson->setPersonal($personal);
                $manager->persist($chargePerson);
            }
            $manager->persist($personal);

            /** Service pour le calcule des impôts sur salaire du salarié */
            $salary->chargePersonal($personal);

            $manager->flush();
            flash()->addSuccess('Personne à la charge du personel ajouté avec succès.');
            return $this->redirectToRoute('charge_people_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('dossier_personal/charge_people/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Personal               $personal,
        EntityManagerInterface $manager,
        SalaryImpotsService    $salary
    ): Response
    {
        $form = $this->createForm(ChargeType::class, [
            'personal' => $personal,
            'chargePeople' => $personal->getChargePeople()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getChargePeople() as $chargePerson) {
                $chargePerson->setPersonal($personal);
                $manager->persist($chargePerson);
            }

            /** Service pour le calcule des impôts sur salaire du salarié */
            $salary->chargePersonal($personal);

            $manager->flush();
            flash()->addSuccess('Personne à la charge du personel modifié avec succès.');
            return $this->redirectToRoute('charge_people_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/charge_people/edit.html.twig', [
            'charges' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }
}