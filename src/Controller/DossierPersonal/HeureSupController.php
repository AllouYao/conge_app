<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\HeureSup;
use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\HeureSupType;
use App\Form\DossierPersonal\PersonalHeureSupType;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\HeureSupService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dossier/personal/heure_sup', name: 'personal_heure_sup_')]
class HeureSupController extends AbstractController
{
    private $entityManager;
    private $heureSupService;
    private $personalRepository;
    private $heureSupRepository;


    public function __construct(EntityManagerInterface $entityManager, HeureSupService $heureSupService, PersonalRepository $personalRepository, HeureSupRepository $heureSupRepository )
    {
        $this->entityManager = $entityManager;
        $this->heureSupService = $heureSupService;
        $this->personalRepository = $personalRepository;
        $this->heureSupRepository = $heureSupRepository;



    }
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('dossier_personal/heure_sup/index.html.twig', [
            'heureSups' => $this->heureSupRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request,): Response {

        $heureSup = new HeureSup();
        
        $form = $this->createForm(PersonalHeureSupType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PersonalHeureSup = $form->get('heureSup')->getData();
            $personal = $form->get('personal')->getData();
            
            foreach ($PersonalHeureSup as $heureSup) {
                
                // Heure debut
                $StartedfullDate = $heureSup->getStartedDate();
                $StartedfullTime = $heureSup->getStartedHour();
                
                $startedDate = $StartedfullDate->format('Y-m-d');
                $startedHour = $StartedfullTime->format('H:i:s');
                
                $fullNewDateTime = $startedDate.''. $startedHour;
                
                $newFullDate = new DateTime($fullNewDateTime);
                
                $heureSup->setStartedHour($newFullDate);

                // Heure fin
                $endedfullDate = $heureSup->getEndedDate();
                $endedfullTime = $heureSup->getEndedHour();

                $endedDate = $endedfullDate->format('Y-m-d');
                $endedHour = $endedfullTime->format('H:i:s');

                $fullNewDateTime = $endedDate . '' . $endedHour;

                $newFullDate = new DateTime($fullNewDateTime);

                $heureSup->setEndedHour($newFullDate);
                
                
                $heureSup->setPersonal($personal);
                $this->entityManager->persist($heureSup);
            }
            
            $this->entityManager->persist($personal);
            
            $this->entityManager->flush();
            flash()->addSuccess('Heure suplementaire ajouté avec succès.');
            return $this->redirectToRoute('personal_heure_sup_index', [], Response::HTTP_SEE_OTHER);
        }

/* test
        $personal = $this->personalRepository->find(3);

        $heureSups =  $this->heureSupService->getAmountByMonth($personal, 12, 2023);

        dd($heureSups);
*/
        
        return $this->render('dossier_personal/heure_sup/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Personal $personal, Request $request): Response {
        $form = $this->createForm(PersonalHeureSupType::class, [
            'personal' => $personal,
            'heureSup' => $personal->getHeureSups(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getHeureSups() as $heureSup) {
                $heureSup->setPersonal($personal);
                $this->entityManager->persist($heureSup);
            }

            $this->entityManager->flush();
            flash()->addSuccess('Heure suplementaire modifié avec succès.');
            return $this->redirectToRoute('personal_heure_sup_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/heure_sup/edit.html.twig', [
            'charges' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }
    

}