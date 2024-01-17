<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\PersonalHeureSupType;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\HeureSupService;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
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


    public function __construct(
        EntityManagerInterface $entityManager,
        PersonalRepository     $personalRepository,
        HeureSupRepository     $heureSupRepository,
    )
    {
        $this->entityManager = $entityManager;
        $this->personalRepository = $personalRepository;
        $this->heureSupRepository = $heureSupRepository;
    }

    #[Route('/api/heure_supp_super_book', name: 'api_heure_supp_super_book', methods: ['GET'])]
    public function apiBookHour(): JsonResponse
    {

        $jourNormalOrFerie = null;
        $jourOrNuit = null;
        $index = 0;
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $personals = $this->personalRepository->findAllPersonal();
        $apiHeureSupp = [];
        $salaireHoraire = 0;
        foreach ($personals as $personal) {
            $heureSupp = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
            //$heureSupp = $this->heureSupRepository->findAll();
            $statut = $personal->getContract()->getTempsContractuel() === Status::TEMPS_PLEIN ? 'PERMANENT' : 'VACATAIRES';
            $fullnamePersonal = $personal->getFirstName() . ' ' . $personal->getLastName();
            $personalSalaireBase = $personal->getCategorie()->getAmount();
            $amountHoraire = 0;
            $heure_15 = 0;
            $heure_50 = 0;
            $heure_75_jour = 0;
            $heure_75_nuit = 0;
            $heure_100 = 0;
            $totalHeure = 0;
            if (count($heureSupp) > 0) {
                foreach ($heureSupp as $item) {
                    $salaireHoraire = $personalSalaireBase / (double)$item->getTauxHoraire();
                    $heure = (int)$item->getTotalHorraire();
                    $jourNormalOrFerie = $item->getTypeDay();
                    $jourOrNuit = $item->getTypeJourOrNuit();
                    $totalHeure += (int)$item->getTotalHorraire();
                    if ($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $heure <= 6) {
                        $heure_15 += $heure;
                    } elseif ($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $heure > 6) {
                        $heure_50 += $heure;
                    } elseif ($jourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                        $heure_75_jour += $heure;
                    } elseif (($jourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT)) {
                        $heure_75_nuit += $heure;
                    } elseif ($jourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                        $heure_100 += $heure;
                    }
                    $amountHoraire = $amountHoraire + $item->getAmount();
                }
                $apiHeureSupp[] = [
                    'index' => ++$index,
                    'full_name' => $fullnamePersonal,
                    'heure_normal' => 40,
                    'total_heure' => $totalHeure,
                    'typeDay' => $jourNormalOrFerie,
                    'typeJour' => $jourOrNuit,
                    'heure_15_%' => $heure_15,
                    'heure_50_%' => $heure_50,
                    'heure_75_%_jour' => $heure_75_jour,
                    'heure_75_%_nuit' => $heure_75_nuit,
                    'heure_100_%_nuit' => $heure_100,
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
        $personal = null;
        $heursSupps = $this->heureSupRepository->findAll();
        foreach ($heursSupps as $supp) {
            $personal = $supp->getPersonal();
        }
        $requestHeursSupp = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
        //$requestHeursSupp = $this->heureSupRepository->findAll();
        $apiRequestHeureSupp = [];
        foreach ($requestHeursSupp as $heureSup) {
            $apiRequestHeureSupp[] = [
                'matricule' => $heureSup->getPersonal()->getMatricule(),
                'name' => $heureSup->getPersonal()->getFirstName(),
                'last_name' => $heureSup->getPersonal()->getLastName(),
                'date_naissance' => date_format($heureSup->getPersonal()->getBirthday(), 'd/m/Y'),
                'categorie_salarie' => '(' . $heureSup->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')' . '-' . $personal->getCategorie()->getIntitule(),
                'date_embauche' => date_format($heureSup->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'date_debut' => date_format($heureSup->getStartedDate(), 'd/m/Y'),
                'heure_debut' => date_format($heureSup->getStartedHour(), 'H:m'),
                'date_fin' => date_format($heureSup->getEndedDate(), 'd/m/Y'),
                'heure_fin' => date_format($heureSup->getEndedHour(), 'H:m'),
                'total_horaire' => $heureSup->getTotalHorraire(),
                'date_creation' => date_format($heureSup->getPersonal()->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('personal_heure_sup_edit', ['uuid' => $heureSup->getPersonal()->getUuid()])
            ];
        }

        return new JsonResponse($apiRequestHeureSupp);
    }

    #[Route('/supp_book', name: 'supp_book', methods: ['GET'])]
    public function heureSuppBook(): Response
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        return $this->render('dossier_personal/heure_sup/sup_book.html.twig', [
            'heureSups' => $this->heureSupRepository->findAll(),
            'mois' => $month,
            'annee' => $years
        ]);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dossier_personal/heure_sup/index.html.twig');
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
            'form' => $form,
            'editing' => true
        ]);
    }
}