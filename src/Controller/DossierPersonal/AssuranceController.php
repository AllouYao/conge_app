<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Entity\User;
use App\Form\DossierPersonal\AssurancePersonalType;
use App\Form\DossierPersonal\AssuranceType;
use App\Repository\DossierPersonal\ChargePeopleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assurance', name: 'personal_assurance_')]
class AssuranceController extends AbstractController
{
    private ChargePeopleRepository $repository;

    public function __construct(ChargePeopleRepository $repository)
    {
        $this->repository = $repository;
    }

    #[Route('/api_json_charge_people/{personalId}', name: 'api_charge_people', methods: ['GET', 'POST'])]
    public function updateChargePeaple($personalId): JsonResponse
    {
        $chargePeaple = $this->repository->findPeopleByPersonalId($personalId)->getQuery()->getResult();
        $data = [];
        foreach ($chargePeaple as $charge) {
            $data[$charge->getId()] = $charge->getFirstName() . ' ' . $charge->getLastName();
        }
        return new JsonResponse($data);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        /**
         * @var User $currentUser
         */
        $assurance = new DetailRetenueForfetaire();
        $currentUser = $this->getUser();
        $form = $this->createForm(AssurancePersonalType::class, $assurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assurance->setUser($currentUser);
            $manager->persist($assurance);
            $manager->flush();
            flash()->addSuccess('Assurance santé ajouter avec succès.');
            return $this->redirectToRoute('');
        }

        return $this->render('dossier_personal/assurance/new.html.twig', [
            'form' => $form->createView(),
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