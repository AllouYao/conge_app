<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Entity\User;
use App\Form\PersonalAbsenceType;
use App\Repository\AbsenceRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
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


    public function __construct(
        EntityManagerInterface $entityManager,
        AbsenceRepository      $absenceRepository,

    )
    {
        $this->entityManager = $entityManager;
        $this->absenceRepository = $absenceRepository;
    }

    #[Route('/api_absence', name: 'api_absence', methods: ['GET'])]
    public function apiAbsence(): JsonResponse
    {
        $now = Carbon::today();
        if ($this->isGranted('ROLE_RH')) {
            $absences = $this->absenceRepository->getAbsenceByMonths($now->month, $now->year);
        } else {
            $absences = $this->absenceRepository->getAbsenceByMonthsByEmployeRole($now->month, $now->year);
        }
        $apiAbsences = [];
        foreach ($absences as $absence) {
            $deducteur = $this->absenceService->getAmountDeduction($absence);
            $apiAbsences[] = [
                'matricule' => $absence->getPersonal()->getMatricule(),
                'name' => $absence->getPersonal()->getFirstName(),
                'last_name' => $absence->getPersonal()->getLastName(),
                'date_naissance' => $absence->getPersonal()->getBirthday() ? date_format($absence->getPersonal()->getBirthday(), 'd/m/Y') : '',
                'categorie_salarie' => '(' . $absence->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' .
                    '-' . $absence->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($absence->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'type_absence' => $absence->getType(),
                'date_depart' => date_format($absence->getStartedDate(), 'd/m/Y'),
                'date_retour' => date_format($absence->getEndedDate(), 'd/m/Y'),
                'status' => $absence->isJustified() ? 'OUI' : 'NON',
                'description' => $absence->getDescription(),
                'duree_jour' => $absence->getTotalDay(),
                'montant_deduit' => ceil($deducteur),
                'date_creation' => date_format($absence->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('personal_absence_edit', ['uuid' => $absence->getPersonal()->getUuid()])
            ];
        }
        return new JsonResponse($apiAbsences);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $absence = $this->absenceRepository->findAll();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
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
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();


        $form = $this->createForm(PersonalAbsenceType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $personalAbsence = $form->get('absencess')->getData();
            $personal = $form->get('personal')->getData();
            if ($form->get('absence')->count() == 0) {
                flash()->addInfo('Veuillez s\'il vous plaît ajouter au moins une ligne pour continuer merci !');
                return $this->redirectToRoute('personal_absence_new');
            }
            foreach ($personalAbsence as $absence) {
                $absence->setPersonal($personal);
                $absence->setUser($currentUser);
                $this->entityManager->persist($absence);
            }

            $this->entityManager->persist($personal);

            $this->entityManager->flush();
            flash()->addSuccess('Absence ajouté avec succès.');
            return $this->redirectToRoute('personal_absence_index', [], Response::HTTP_SEE_OTHER);
        }


        return $this->render('dossier_personal/absence/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Personal $personal, Request $request): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();

        $form = $this->createForm(PersonalAbsenceType::class, [
            'personal' => $personal,
            'absence' => $personal->getAbsences(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getAbsences() as $absence) {
                $absence->setPersonal($personal);
                $this->entityManager->persist($absence);
                $absence->setUser($currentUser);
            }

            $this->entityManager->flush();
            flash()->addSuccess('Absence modifié avec succès.');
            return $this->redirectToRoute('personal_absence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/absence/edit.html.twig', [
            'personals' => $personal,
            'form' => $form->createView(),
            'editing' => true
        ]);
    }

}