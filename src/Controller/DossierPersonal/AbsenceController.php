<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\PersonalAbsence;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\AbsenceService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dossier/personal/absence', name: 'personal_absence_')]

class AbsenceController extends AbstractController
{
    private $entityManager;
    private $absenceRepository;
    private $absenceService;
    private $PersonalRepository;

    


    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository $absenceRepository,
        AbsenceService $absenceService,
        PersonalRepository $personalRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
        $this->absenceService = $absenceService;
        $this->PersonalRepository = $personalRepository;
    }
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $fullDate = new DateTime();
        $month = $fullDate->format("m");
        $year = $fullDate->format("Y");
        
        $personal = $this->PersonalRepository->find(3);
        
        $totalAmount = $this->absenceService->getAmountByMonth($personal,$month, $year);
        // dd($totalAmount);
        
        return $this->render('dossier_personal/absence/index.html.twig', [
            'absences' => $this->absenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request,): Response {

        $form = $this->createForm(PersonalAbsence::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PersonalAbsence = $form->get('absence')->getData();
            $personal = $form->get('personal')->getData();
            
            foreach ($PersonalAbsence as $absence) {

                $absence->setPersonal($personal);
                $this->entityManager->persist($absence);
            }
            $this->entityManager->persist($personal);
            
            $this->entityManager->flush();
            flash()->addSuccess('Absence ajouté avec succès.');
            return $this->redirectToRoute('personal_absence_index', [], Response::HTTP_SEE_OTHER);
        }


        
        return $this->render('dossier_personal/absence/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Personal $personal, Request $request): Response {
        $form = $this->createForm(PersonalAbsence::class, [
            'personal' => $personal,
            'absence' => $personal->getAbsences(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getAbsences() as $absence) {
                $absence->setPersonal($personal);
                $this->entityManager->persist($absence);
            }

            $this->entityManager->flush();
            flash()->addSuccess('Absence modifié avec succès.');
            return $this->redirectToRoute('personal_absence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/absence/edit.html.twig', [
            'absence' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }
    

}