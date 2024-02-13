<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\PersonalAbsenceType;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Service\AbsenceService;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dossier/personal/absence', name: 'personal_absence_')]
class AbsenceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AbsenceRepository $absenceRepository;
    private AbsenceService $absenceService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository      $absenceRepository,
        AbsenceService         $absenceService
    )
    {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
        $this->absenceService = $absenceService;
    }

    #[Route('/api_absence', name: 'api_absence', methods: ['GET'])]
    public function apiAbsence(): JsonResponse
    {
        $now = Carbon::today();
        $absence = $this->absenceRepository->findAll();
        $personal = null;
        foreach ($absence as $item) {
            $personal = $item->getPersonal();
        }
        $apiAbsences = [];
        $absencesRequests = $this->absenceRepository->getAbsenceByMonth($personal, $now->month, $now->year);
        foreach ($absencesRequests as $absences) {
            $newBaseAmount = $this->absenceService->getAmountByMonth($personal, $now->month, $now->year);
            $apiAbsences[] = [
                'matricule' => $absences->getPersonal()->getMatricule(),
                'name' => $absences->getPersonal()->getFirstName(),
                'last_name' => $absences->getPersonal()->getLastName(),
                'date_naissance' => date_format($absences->getPersonal()->getBirthday(), 'd/m/Y'),
                'categorie_salarie' => '(' . $absences->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' .
                    '-' . $absences->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($absences->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'type_absence' => $absences->getType(),
                'date_depart' => date_format($absences->getStartedDate(), 'd/m/Y'),
                'date_retour' => date_format($absences->getEndedDate(), 'd/m/Y'),
                'status' => $absences->isJustified() ? 'OUI' : 'NON',
                'description' => $absences->getDescription(),
                'duree_jour' => $absences->getTotalDay(),
                'nouveau_salaire_base' => $newBaseAmount,
                'date_creation' => date_format($absences->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('personal_absence_edit', ['uuid' => $absences->getPersonal()->getUuid()])
            ];
        }

        return new JsonResponse($apiAbsences);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $absence = $this->absenceRepository->findAll();
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $today = Carbon::now();
        $date = $formatter->format($today);


        return $this->render('dossier_personal/absence/index.html.twig', [
            'absences' => $absence,
            'date' => $date
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $form = $this->createForm(PersonalAbsenceType::class);
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
    public function edit(Personal $personal, Request $request): Response
    {
        $form = $this->createForm(PersonalAbsenceType::class, [
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
            'personals' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }


}