<?php

namespace App\Controller\DossierPersonal;

use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Departure;
use App\Entity\User;
use App\Form\DossierPersonal\DepartureType;
use App\Repository\DossierPersonal\DepartureRepository;
use App\Service\CasExeptionel\PaieOutService;
use App\Service\DepartServices;
use App\Service\UtimeDepartServ;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier/personal/departure', name: 'departure_')]
class DepartureController extends AbstractController
{
    private DepartureRepository $departureRepository;
    private DepartServices $departServices;
    private UtimeDepartServ $utimeDepartServ;
    private PaieOutService $paieOutService;

    public function __construct(
        DepartureRepository $departureRepository,
        DepartServices      $departServices,
        UtimeDepartServ $utimeDepartServ,
        PaieOutService $paieOutService,
        private readonly SalaryInterface $salary_interface
    )
    {
        $this->departureRepository = $departureRepository;
        $this->departServices = $departServices;
        $this->utimeDepartServ = $utimeDepartServ;
        $this->paieOutService = $paieOutService;
    }

    /**
     * @throws Exception
     */
    #[Route('/api/{typeDepart}/depart', name: 'api', methods: ['GET'])]
    public function apiDepart($typeDepart): JsonResponse
    {
        $codeDepart = Status::REASONCODE[$typeDepart];
        $index = 0;
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        $apiDeparture = [];

        if ($this->isGranted('ROLE_RH')) {

            $departures = $this->departureRepository->getDepartureByDate($month, $years, $codeDepart);

        } else {

            $departures = $this->departureRepository->getDepartureByDateByEmployeRole($month, $years, $codeDepart);

        }
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

    #[Route('/{typeDepart}/index', name: 'index', methods: ['GET'])]
    public function index(DepartureRepository $departureRepository, $typeDepart): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/departure/index.html.twig', [
            'date' => $date,
            'typeDepart' => $typeDepart,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/new/{typeDepart}', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager, $typeDepart): Response
    {
        /**
         * @var User $current_user
         */

        $type_departs = [
            'demission' => 'Démission',
            'retraite' => 'Retraite',
            'licenciement_lourde' => 'Licenciement faute lourde',
            'licenciement_simple' => 'Licenciement faute simple',
            'deces' => 'Décès',
        ];

        $current_user = $this->getUser();
        $departure = new Departure();
        $departure->setReason($type_departs[$typeDepart]);
        $forms = $this->createForm(DepartureType::class, $departure);
        $forms->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $departure->setReasonCode(Status::REASONCODE[$typeDepart]);
            $departure->setUser($current_user);
            $manager->persist($departure);
            $this->salary_interface->chargPersonalOut($departure);
            $this->salary_interface->chargEmployerOut($departure);
            //$this->departServices->calculeDroitsAndIndemnity($departure);
            $dpees = $this->utimeDepartServ->getIndemniteConges($departure);
            $paie_out = $this->paieOutService->getAssurance($departure);
            dd(['utime_depart' => $dpees, 'paie_out' => $paie_out, 'departure' => $departure]);
            $manager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', ['typeDepart' => $typeDepart], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $forms->createView(),
            'typeDepart' => $typeDepart,
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
            'typeDepart' => 'demission',
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
