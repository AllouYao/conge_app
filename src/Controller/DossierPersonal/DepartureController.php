<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Departure;
use App\Form\DossierPersonal\DepartureType;
use App\Repository\DossierPersonal\DepartureRepository;
use App\Service\DepartServices;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
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
            $congeElements = $this->departServices->getCongeElementInDepart($departure);
            $anciennete = $this->departServices->getAncienneteByDepart($departure);
            $dureePreavis = $this->departServices->getPreavisByDepart($anciennete['ancienneteYear']);
            $indemnitePreavis = $this->departServices->getIndemnitePreavisByDepart($departure);
            $apiDeparture[] = [
                'index' => ++$index,
                'full_name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                'dateCessation' => date_format($departure->getDate(), 'd/m/Y'),
                'motifCessation' => $departure->getReason(),
                'salaireMoyen' => $congeElements['salaireMoyen'],
                'gratification' => $congeElements['gratification'],
                'dateRetourDrnConges' => $congeElements['dateDernierConge'],
                'indemniteConges' => $congeElements['indemniteConge'],
                'preavis' => $dureePreavis, // le préavis ici est determiné en mois
                'indemnitePreavis' => $indemnitePreavis,
                'anciennete' => $anciennete['ancienneteMonth'],
                'indemniteCessation' => null,
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

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departure = new Departure();
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->departServices->rightAndIndemnityByDeparture($departure);
            $entityManager->persist($departure);
            $entityManager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/show/{uuid}', name: 'show', methods: ['GET'])]
    public function show(Departure $departure): Response
    {
        return $this->render('dossier_personal/departure/show.html.twig', [
            'departure' => $departure,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departure $departure, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
