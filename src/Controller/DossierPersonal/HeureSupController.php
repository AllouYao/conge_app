<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\PersonalHeureSupType;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\HeureSupService;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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


    public function __construct(
        EntityManagerInterface $entityManager,
        HeureSupService        $heureSupService,
        PersonalRepository     $personalRepository,
        HeureSupRepository     $heureSupRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->heureSupService = $heureSupService;
        $this->personalRepository = $personalRepository;
        $this->heureSupRepository = $heureSupRepository;
    }

    #[Route('/api/heure_supp', name: 'api_heure_supp', methods: ['GET'])]
    public function apiHeureSupp(): JsonResponse
    {
        $heure_15 = 0;
        $heure_50 = 0;
        $heure_75_jour = 0;
        $heure_75_nuit = 0;
        $heure_100 = 0;
        $totalHeure = 0;
        $index = 0;
        $jourNormalOrFerie = null;
        $jourOrNuit = null;
        $amountHoraire = null;

        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $personals = $this->personalRepository->findAllPersonal();

        $apiHeureSupp = [];
        foreach ($personals as $personal) {
            $heureSupp = $this->heureSupRepository->getHeureSupByDate($personal, $month, $years);
            if ($heureSupp) {
                foreach ($heureSupp as $item) {
                    $fullnamePersonal = $personal->getFirstName() . ' ' . $personal->getLastName();
                    $personalSalaireBase = $personal->getCategorie()->getAmount();
                    $statut = $personal->getContract()->getTempsContractuel() === Status::TEMPS_PLEIN ? 'PERMANENT' : 'VACATAIRES';
                    $tauxHoraire = ceil($personalSalaireBase / Status::TAUX_HEURE);
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
                    $amountHoraire = $this->heureSupService->getAmountHeursSupp($personal);

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
                    'taux_horaire' => $tauxHoraire,
                    'montant_heure_supp' => $amountHoraire,
                    'status' => $statut,
                ];

                $heure_15 = 0;
                $heure_50 = 0;
                $heure_75_jour = 0;
                $heure_75_nuit = 0;
                $heure_100 = 0;
                $totalHeure = 0;
                $jourNormalOrFerie = null;
                $jourOrNuit = null;
                $amountHoraire = null;
            }
        }
        return new JsonResponse($apiHeureSupp);
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
        return $this->render('dossier_personal/heure_sup/index.html.twig', [
            'heureSups' => $this->heureSupRepository->findAll(),
        ]);
    }


    /**
     * @throws Exception
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request,): Response
    {
        $form = $this->createForm(PersonalHeureSupType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $PersonalHeureSup = $form->get('heureSup')->getData();
            $personal = $form->get('personal')->getData();
            $tauxHoraire = (double)$personal->getSalary()->getTauxHoraire();
            $salaireBase = (int)$personal->getCategorie()->getAmount();
            $salaireHoraire = ceil($salaireBase / $tauxHoraire);

            foreach ($PersonalHeureSup as $heureSup) {
                // Heure debut
                $StartedfullDate = $heureSup->getStartedDate();
                $StartedfullTime = $heureSup->getStartedHour();
                $startedDate = $StartedfullDate->format('Y-m-d');
                $startedHour = $StartedfullTime->format('H:i:s');
                $fullNewDateTime = $startedDate . '' . $startedHour;
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

                $JourNormalOrFerie = $heureSup->getTypeDay(); // normal/Férié/dimanche
                $startedHour = $heureSup->getStartedHour(); // heure debut
                $endedHour = $heureSup->getEndedHour(); // heure fin
                $jourOrNuit = $heureSup->getTypeJourOrNuit(); // Jour/nuit
                $diffHours = $startedHour->diff($endedHour);
                $totalHorraire = (int)$diffHours->format('%h');
                $amountHeureSup = 0;
                if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire <= 6) {
                    // 15% jour normal ~ 115%
                    $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE) * $totalHorraire;
                } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $totalHorraire > 6) {
                    // 50% jour normal ~ 150%
                    $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_JOUR_OUVRABLE_EXTRA) * $totalHorraire;
                } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                    // 75% jour ferié or dimanche nuit ~ 175%
                    $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
                } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                    // 75% jour ferié or dimanche nuit ~ 175%
                    $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_OUVRABLE_OR_NON_OUVRABLE) * $totalHorraire;
                } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                    // 75% jour ferié or dimanche nuit ~ 200%
                    $amountHeureSup = $amountHeureSup + ($salaireHoraire * Status::TAUX_NUIT_NON_OUVRABLE) * $totalHorraire;
                }

                $personal->getSalary()->setHeursupplementaire($amountHeureSup);
                $heureSup->setPersonal($personal);
                $this->entityManager->persist($heureSup);
            }
            $this->entityManager->flush();
            flash()->addSuccess('Heure suplementaire ajouté avec succès.');
            return $this->redirectToRoute('personal_heure_sup_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/heure_sup/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Personal $personal, Request $request): Response
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