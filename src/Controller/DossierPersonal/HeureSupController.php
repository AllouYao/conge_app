<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DevPaie\WorkTime;
use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\PersonalHeureSupType;
use App\Repository\DevPaie\WorkTimeRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\HeureSupService;
use App\Service\Personal\ChargesServices;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dossier/personal/heure_sup', name: 'personal_heure_sup_')]
class HeureSupController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PersonalRepository $personalRepository;
    private HeureSupRepository $heureSupRepository;
    private ChargesServices $chargesServices;


    public function __construct(
        EntityManagerInterface $entityManager,
        PersonalRepository     $personalRepository,
        HeureSupRepository     $heureSupRepository,
        ChargesServices        $chargesServices
    )
    {
        $this->entityManager = $entityManager;
        $this->personalRepository = $personalRepository;
        $this->heureSupRepository = $heureSupRepository;
        $this->chargesServices = $chargesServices;
    }

    #[Route('/api/heure_supp_super_book', name: 'api_heure_supp_super_book', methods: ['GET'])]
    public function apiBookHour(WorkTimeRepository $workTimeRepository): JsonResponse
    {

        $defaultRate = 100;
        $jourNormalOrFerie = null;
        $jourOrNuit = null;
        $index = 0;
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $apiHeureSupp = [];
        $salaireHoraire = 0;

        if ($this->isGranted('ROLE_RH')) {

            $personals = $this->personalRepository->findAllPersonal();

        } else {

            $personals = $this->personalRepository->findAllPersonalByEmployeRole();
        }


        foreach ($personals as $personal) {
            $heureSupp = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
            $statut = $personal->getContract()->getTempsContractuel() === Status::TEMPS_PLEIN ? 'PERMANENT' : 'VACATAIRES';
            $fullnamePersonal = $personal->getFirstName() . ' ' . $personal->getLastName();
            $personalSalaireBase = $this->chargesServices->amountSalaireBrutAndImposable($personal)['salaire_categoriel'];
            $amountHoraire = 0;
            $heure_15 = 0;
            $heure15 = 0;
            $heure_50 = 0;
            $heure50 = 0;
            $heure_75_jour = 0;
            $heure75Jour = 0;
            $heure_75_nuit = 0;
            $heure75Nuit = 0;
            $heure_100 = 0;
            $heure100 = 0;
            $totalHeure = 0;
            if (count($heureSupp) > 0) {
                foreach ($heureSupp as $item) {
                    $salaireHoraire = $personalSalaireBase / (double)$item->getTauxHoraire();
                    $heure = (int)$item->getTotalHorraire();
                    $jourNormalOrFerie = $item->getTypeDay();
                    $jourOrNuit = $item->getTypeJourOrNuit();
                    $totalHeure += (int)$item->getTotalHorraire();

                    $workTime = $workTimeRepository->findOneBy(['type' => 'MAJORATION_15_PERCENT']);
                    if ($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $heure <= $workTime->getHourValue()?? 6) {
                        $heure_15 += $heure;
                        $heure15 = $heure_15;
                    } elseif ($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $heure > $workTime->getHourValue()?? 6) {
                        $heure_6 = 6;
                        $heure15 += $heure_15;
                        $heure_50 += $heure - $heure_6;
                        $heure50 = $heure_50;
                    } elseif ($jourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                        $heure_75_jour += $heure;
                        $heure75Jour = $heure_75_jour;
                    } elseif (($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT)) {
                        $heure_75_nuit += $heure;
                        $heure75Nuit = $heure_75_nuit;
                    } elseif ($jourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                        $heure_100 += $heure;
                        $heure100 = $heure_100;
                    }
                    $amountHoraire = $amountHoraire + $item->getAmount();
                }
                $apiHeureSupp[] = [
                    'index' => ++$index,
                    'heureSuppId' => ++$index,
                    'full_name' => $fullnamePersonal,
                    'heure_normal' => 40,
                    'total_heure' => $totalHeure,
                    'typeDay' => $jourNormalOrFerie,
                    'typeJour' => $jourOrNuit,
                    'heure_15_%' => $heure15,
                    'heure_50_%' => $heure50,
                    'heure_75_%_jour' => $heure75Jour,
                    'heure_75_%_nuit' => $heure75Nuit,
                    'heure_100_%_nuit' => $heure100,
                    'taux_horaire' => $salaireHoraire,
                    'montant_heure_supp' => $amountHoraire,
                    'status' => $statut,
                ];
            }

        }
        return new JsonResponse($apiHeureSupp);
    }

    #[Route('/api/heure_supp', name: 'api_heure_supplementaire', methods: ['GET'])]
    public function apiHeureSupp(): JsonResponse
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        if ($this->isGranted('ROLE_RH')) {

            $heursSupps = $this->heureSupRepository->getAllByDate($month, $years);

        } else {

            $heursSupps = $this->heureSupRepository->findHeureSupByEmployeRole($month, $years);
        }
        $apiRequestHeureSupp = [];
        foreach ($heursSupps as $heureSup) {
            $apiRequestHeureSupp[] = [
                'id' => $heureSup->getId(),
                'matricule' => $heureSup->getPersonal()->getMatricule(),
                'name' => $heureSup->getPersonal()->getFirstName(),
                'last_name' => $heureSup->getPersonal()->getLastName(),
                'date_naissance' => $heureSup->getPersonal()->getBirthday() ? date_format($heureSup->getPersonal()->getBirthday(), 'd/m/Y') : '',
                'categorie_salarie' => '(' . $heureSup->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' . '-' . $heureSup->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($heureSup->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'date_debut' => date_format($heureSup->getStartedDate(), 'd/m/Y'),
                'heure_debut' => date_format($heureSup->getStartedHour(), 'H:m'),
                'date_fin' => date_format($heureSup->getEndedDate(), 'd/m/Y'),
                'heure_fin' => date_format($heureSup->getEndedHour(), 'H:m'),
                'total_horaire' => $heureSup->getTotalHorraire(),
                'date_creation' => date_format($heureSup->getCreatedAt(), 'd/m/Y'),
                'status' => $heureSup->getStatus(),
                'modifier' => $this->generateUrl('personal_heure_sup_edit', ['uuid' => $heureSup->getPersonal()->getUuid()])
            ];
        }

        return new JsonResponse($apiRequestHeureSupp);
    }
    #[Route('/api/heure_supp/pending ', name: 'api_heure_supplementaire_pending', methods: ['GET'])]
    public function apiHeureSuppPending(): JsonResponse
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        if ($this->isGranted('ROLE_RH')) {
            $heursSupps = $this->heureSupRepository->getByStatus($month, $years, Status::EN_ATTENTE);
        } else {
            $heursSupps = $this->heureSupRepository->findHeureSupByStatusByEmployeRole($month, $years, Status::EN_ATTENTE);
        }
        $apiRequestHeureSupp = [];
        foreach ($heursSupps as $heureSup) {
            $apiRequestHeureSupp[] = [
                'id' => $heureSup->getId(),
                'matricule' => $heureSup->getPersonal()->getMatricule(),
                'name' => $heureSup->getPersonal()->getFirstName(),
                'last_name' => $heureSup->getPersonal()->getLastName(),
                'date_naissance' => $heureSup->getPersonal()->getBirthday() ? date_format($heureSup->getPersonal()->getBirthday(), 'd/m/Y') : '',
                'categorie_salarie' => '(' . $heureSup->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' . '-' . $heureSup->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($heureSup->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'date_debut' => date_format($heureSup->getStartedDate(), 'd/m/Y'),
                'heure_debut' => date_format($heureSup->getStartedHour(), 'H:m'),
                'date_fin' => date_format($heureSup->getEndedDate(), 'd/m/Y'),
                'heure_fin' => date_format($heureSup->getEndedHour(), 'H:m'),
                'total_horaire' => $heureSup->getTotalHorraire(),
                'date_creation' => date_format($heureSup->getCreatedAt(), 'd/m/Y'),
                'status' => $heureSup->getStatus(),
                'modifier' => $this->generateUrl('personal_heure_sup_edit', ['uuid' => $heureSup->getPersonal()->getUuid()])
            ];
        }
        return new JsonResponse($apiRequestHeureSupp);
    }
    #[Route('/api/heure_supp/validated ', name: 'api_heure_supplementaire_validated', methods: ['GET'])]
    public function apiHeureSuppValidate(): JsonResponse
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        if ($this->isGranted('ROLE_RH')) {

            $heursSupps = $this->heureSupRepository->getByStatus($month, $years, Status::VALIDATED);

        } else {
            $heursSupps = $this->heureSupRepository->findHeureSupByStatusByEmployeRole($month, $years, Status::VALIDATED);
        }
        $apiRequestHeureSupp = [];
        foreach ($heursSupps as $heureSup) {
            $apiRequestHeureSupp[] = [
                'id' => $heureSup->getId(),
                'matricule' => $heureSup->getPersonal()->getMatricule(),
                'name' => $heureSup->getPersonal()->getFirstName(),
                'last_name' => $heureSup->getPersonal()->getLastName(),
                'date_naissance' => $heureSup->getPersonal()->getBirthday() ? date_format($heureSup->getPersonal()->getBirthday(), 'd/m/Y') : '',
                'categorie_salarie' => '(' . $heureSup->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' . '-' . $heureSup->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($heureSup->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'date_debut' => date_format($heureSup->getStartedDate(), 'd/m/Y'),
                'heure_debut' => date_format($heureSup->getStartedHour(), 'H:m'),
                'date_fin' => date_format($heureSup->getEndedDate(), 'd/m/Y'),
                'heure_fin' => date_format($heureSup->getEndedHour(), 'H:m'),
                'total_horaire' => $heureSup->getTotalHorraire(),
                'date_creation' => date_format($heureSup->getCreatedAt(), 'd/m/Y'),
                'status' => $heureSup->getStatus(),
                'modifier' => $this->generateUrl('personal_heure_sup_edit', ['uuid' => $heureSup->getPersonal()->getUuid()])
            ];
        }

        return new JsonResponse($apiRequestHeureSupp);
    }

    #[Route('/supp_book', name: 'supp_book', methods: ['GET'])]
    public function heureSuppBook(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);

        return $this->render('dossier_personal/heure_sup/sup_book.html.twig', [
            'heureSups' => $this->heureSupRepository->findAll(),
            'date' => $date
        ]);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/heure_sup/index.html.twig', [
            'date' => $date
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, HeureSupService $supService): Response
    {
        $form = $this->createForm(PersonalHeureSupType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $form->get('personal')->getData();
            $data = $form->getData();
            $supService->heureSupp($data, $personal);
            $this->entityManager->flush();
            flash()->addSuccess('Heure suplementaire ajouté avec succès.');
            return $this->redirectToRoute('personal_heure_sup_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/heure_sup/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HeureSupService $heureSupService, Personal $personal): Response
    {
        $form = $this->createForm(PersonalHeureSupType::class, [
            'personal' => $personal,
            'heureSup' => $personal->getHeureSups()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getHeureSups() as $heureSup) {
                $heureSup->setPersonal($personal);
                $this->entityManager->persist($heureSup);
            }
            $data = $form->getData();
            $heureSupService->heureSupp($data, $personal);
            $this->entityManager->flush();
            flash()->addSuccess('Heure suplementaire modifié avec succès.');
            return $this->redirectToRoute('personal_heure_sup_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('dossier_personal/heure_sup/edit.html.twig', [
            'personals' => $personal,
            'form' => $form->createView(),
            'editing' => true
        ]);
    }
    #[Route('/pending', name: 'pending', methods: ['GET'])]
    public function pending(): Response
    {
        return $this->render('dossier_personal/heure_sup/pending.html.twig');
    }
    #[Route('/validated', name: 'validated', methods: ['GET'])]
    public function validated(): Response
    {
        return $this->render('dossier_personal/heure_sup/validated.html.twig');
    }
    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request ): Response
    {
        if ($request->request->has('heureSupInput') && $request->isMethod('POST')) {

            $heureSupInput = $request->request->get('heureSupInput');
            $heureSups = json_decode($heureSupInput);

            if ($heureSups) {

                foreach ($heureSups as $heureSupId) {
                    $heureSup = $this->heureSupRepository->findOneBy(['id' => $heureSupId]);
                    if ($heureSup) {
                        $heureSup->setStatus(Status::VALIDATED);
                        $this->entityManager->persist($heureSup);
                     }
                     $this->entityManager->flush();
                     flash()->addSuccess('Heure supplémentaire validé avec succès!');
                 
                }
            } else {
                flash()->addWarning('Aucune heure supplementaire sélectionnée');
                return $this->redirectToRoute('personal_heure_sup_pending', [], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->redirectToRoute('personal_heure_sup_pending', [], Response::HTTP_SEE_OTHER);
    }
}