<?php

namespace App\Controller\DossierPersonal;

use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\Salary;
use App\Form\DossierPersonal\PersonalType;
use App\Repository\DossierPersonal\PersonalRepository;
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
    public function new(Request $request, EntityManagerInterface $entityManager,SalaryInterface $salary): Response
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
            $salary->chargePersonal($personal);
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
    public function show(Personal $personal): Response
    {
        return $this->render('dossier_personal/personal/show.html.twig', [
            'personal' => $personal,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personal $personal, EntityManagerInterface $entityManager,SalaryImpotsService $salary): Response
    {
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }
            //$salary->chargePersonal($personal);
            /*$part = $salary->getParts($personal);
            $creditImpot = $salary->calculateCreditImpot($personal);
            $impotBrut = $salary->calculerImpotBrut($personal);
            dd($part,$impotBrut,$creditImpot,$impotBrut - $creditImpot);*/
            $salary->chargePersonal($personal);
            $entityManager->flush();
            flash()->addSuccess('Salarié modifier avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form,
            ''
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
