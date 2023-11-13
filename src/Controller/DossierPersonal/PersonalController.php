<?php

namespace App\Controller\DossierPersonal;

use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\Salary;
use App\Form\DossierPersonal\PersonalType;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Service\SalaryImpotsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal', name: 'personal_')]
class PersonalController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PersonalRepository $personalRepository): Response
    {
        return $this->render('dossier_personal/personal/index.html.twig', [
            'personals' => $personalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SalaryInterface $salary): Response
    {
        $personal = new Personal();
        $salaire = (new Salary());
        $contract = (new Contract());
        $personal->setSalary($salaire)->setContract($contract);
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($personal);
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }
            /** Service pour le calcule des impôts sur salaire du salarié et aussi celui dû par l'employeur */
            $salary->chargePersonal($personal);
            $salary->chargeEmployeur($personal);

            $entityManager->flush();
            flash()->addSuccess('Salarié enregistré avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/new.html.twig', [
            'personal' => $personal,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/show', name: 'show', methods: ['GET'])]
    public function show(Personal $personal, ChargePersonalsRepository $chargePersonalsRepository, ChargeEmployeurRepository $chargeEmployeurRepository): Response
    {
        return $this->render('dossier_personal/personal/show.html.twig', [
            'personal' => $personal,
            'charge_personal' => $chargePersonalsRepository->findOneBy(['personal' => $personal]),
            'charge_employeur' => $chargeEmployeurRepository->findOneBy(['personal' => $personal])
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personal $personal, EntityManagerInterface $entityManager, SalaryInterface $salary): Response
    {
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }

            /** Service pour le calcule des impôts sur salaire du salarié et aussi celui dû par l'employeur */
            $salary->chargePersonal($personal);
            $salary->chargeEmployeur($personal);

            $entityManager->flush();
            flash()->addSuccess('Salarié modifier avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }

    #[Route('/{uuid}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Personal $personal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $personal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($personal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('personal_index', [], Response::HTTP_SEE_OTHER);
    }
}
