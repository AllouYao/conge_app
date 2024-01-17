<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Form\DossierPersonal\DepartureType;
use App\Repository\DossierPersonal\DepartureRepository;
use App\Service\DepartServices;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/departure', name: 'departure_')]
class DepartureController extends AbstractController
{
    private DepartureRepository $departureRepository;
    private DepartServices $departServices;

    public function __construct(
        DepartureRepository $departureRepository,
        DepartServices      $departServices
    )
    {
        $this->departureRepository = $departureRepository;
        $this->departServices = $departServices;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
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
            $salaireBase = $personal->getCategorie()->getAmount();
            $reason = $departure->getReason();
            $anciennete = $this->departServices->getAncienneteByDepart($departure);
            $globalElement = $this->departServices->getSalaireGlobalMoyenElement($departure);
            $dernierRetourConger = $globalElement['retourConge'];
            $dureePreavis = null;
            if (
                $reason === Status::LICENCIEMENT_COLLECTIF ||
                $reason === Status::MALADIE ||
                $reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR
            ) {
                $dureePreavis = $this->departServices->getPreavisByDepart($anciennete['ancienneteYear']);
            }

            $apiDeparture[] = [
                'index' => ++$index,
                'full_name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                'dateCessation' => date_format($departure->getDate(), 'd/m/Y'),
                'motifCessation' => $departure->getReason(),
                'salaire_base' => $salaireBase,
                'salaireMoyen' => $departure->getSalaryDue(),
                'gratification' => $departure->getGratification(),
                'dateRetourDrnConges' => $dernierRetourConger ?? null,
                'indemniteConges' => $departure->getCongeAmount(),
                'preavis' => $dureePreavis ?? 0, // le préavis ici est determiné en mois
                'indemnitePreavis' => $departure->getNoticeAmount(),
                'anciennete' => $anciennete['ancienneteMonth'],
                'indemniteCessation' => $departure->getDissmissalAmount(),
                'modifier' => $this->generateUrl('departure_edit', ['uuid' => $departure->getUuid()])
            ];
        }

        return new JsonResponse($apiDeparture);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DepartureRepository $departureRepository): Response
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        return $this->render('dossier_personal/departure/index.html.twig', [
            'departures' => $departureRepository->findAll(),
            'mois' => $month,
            'annee' => $years
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        $departure = new Departure();
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->departServices->rightAndIndemnityByDeparture($departure);
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
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departure $departure, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->departServices->rightAndIndemnityByDeparture($departure);
            $entityManager->flush();
            flash()->addSuccess('Départ modifier avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/edit.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }
}
