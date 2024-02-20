<?php

namespace App\Controller\DossierPersonal;

use Exception;
use Carbon\Carbon;
use App\Entity\User;
use App\Utils\Status;
use IntlDateFormatter;
use App\Service\DepartServices;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DossierPersonal\Departure;
use App\Form\DossierPersonal\DepartureType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\DossierPersonal\DepartureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dossier/personal/departure', name: 'departure_')]
class DepartureController extends AbstractController
{
    private DepartureRepository $departureRepository;
    private DepartServices $departServices;

    public function __construct(
        DepartureRepository $departureRepository,
        DepartServices      $departServices,
    )
    {
        $this->departureRepository = $departureRepository;
        $this->departServices = $departServices;
    }

    #[Route('/api/depart', name: 'api', methods: ['GET'])]
    public function apiDepart(): JsonResponse
    {
        $index = 0;
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $apiDeparture = [];
        $departures = $this->departureRepository->getDepartureByDate($month, $years);
        foreach ($departures as $departure) {
            $personal = $departure->getPersonal();
            $smm = $this->departServices->indemniteCompensatriceCgs($departure)['salaire_moyen_mensuel'];
            $categorySalarie = $personal->getCategorie()->getCategorySalarie()->getName();
            $reason = $departure->getReason();
            $anciennete = $this->departServices->getAncienneteByDepart($departure);
            $dernierRetourConger = $this->departServices->indemniteCompensatriceCgs($departure)['date_dernier_conge'] ?? '';
            $dureePreavis = null;
            if (
                $reason === Status::LICENCIEMENT_COLLECTIF ||
                $reason === Status::MALADIE ||
                $reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR ||
                $reason === Status::RETRAITE
            ) {
                $dureePreavis = $this->departServices->getDrPreavisInMonth($anciennete['anciennity_in_year'], $categorySalarie);
            }

            $deces = null;
            $fraisFuneraire = null;
            $retraite = null;
            $licenciement = null;
            if ($reason === Status::RETRAITE) {
                $retraite = $departure->getDissmissalAmount();
            } elseif ($reason === Status::DECES) {
                $deces = $departure->getDissmissalAmount();
                $fraisFuneraire = $this->departServices->getFraisFuneraire($departure);
            } else {
                $licenciement = $departure->getDissmissalAmount();
            }

            $apiDeparture[] = [
                'index' => ++$index,
                'full_name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                'dateCessation' => date_format($departure->getDate(), 'd/m/Y'),
                'motifCessation' => $departure->getReason(),
                'solde_presence' => $departure->getSalaryDue(),
                'salaireMoyen' => $smm,
                'dateRetourDrnConges' => $dernierRetourConger,
                'gratification' => $departure->getGratification(),
                'indemniteConges' => $departure->getCongeAmount(),
                'preavis' => $dureePreavis ?? 0, // le préavis ici est determiné en mois
                'indemnitePreavis' => $departure->getNoticeAmount(),
                'anciennete' => $anciennete['anciennity_in_month'],
                'indemnite_licenciement' => $licenciement,
                'indemnite_retraite' => $retraite,
                'indemnite_deces' => $deces,
                'frais_funeraire' => $fraisFuneraire,
                'modifier' => $this->generateUrl('departure_edit', ['uuid' => $departure->getUuid()]),
                'sold_tout_compte' => $this->generateUrl('departure_sold_of_all_compte', ['uuid' => $departure->getUuid()]),
                'certificat' => $this->generateUrl('departure_certificat_travail', ['uuid' => $departure->getUuid()])
            ];
        }

        return new JsonResponse($apiDeparture);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DepartureRepository $departureRepository): Response
    {

        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/departure/index.html.twig', [
            'departures' => $departureRepository->findAll(),
            'date' => $date,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();

        $departure = new Departure();
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->departServices->calculeDroitsAndIndemnity($departure);
            $departure->setUser($currentUser);
            $manager->persist($departure);
            $manager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departure $departure, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->departServices->calculeDroitsAndIndemnity($departure);
            $entityManager->flush();
            flash()->addSuccess('Départ modifier avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/edit.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/uuid/sold_of_all_compte', name: 'sold_of_all_compte', methods: ['GET'])]
    public function soldOfAllCompte(): Response
    {
        $departures = $this->departureRepository->findDeparture();
        $departureService = $this->departServices;
        $indemniteCompensConge = $departureService->indemniteCompensatriceCgs($departures);
        $quotePartGratification = $indemniteCompensConge['gratification_prorata'];
        $indemniteCongeAmount = $indemniteCompensConge['indemnite_conge'];
        $indemniteLicenciement = $departureService->getIndemniteLicenciement($departures);

        return $this->render('dossier_personal/departure/sold.html.twig', [
            'gratification' => $quotePartGratification,
            'conge_amount' => $indemniteCongeAmount,
            'licenciement_amount' => $indemniteLicenciement
        ]);
    }

    #[Route('/uuid/certificat_travail', name: 'certificat_travail', methods: ['GET'])]
    public function certificateTravail(): Response
    {
        return $this->render('dossier_personal/departure/certificate.html.twig');
    }
}
