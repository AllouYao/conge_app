<?php

namespace App\Controller\DossierPersonal;

use App\Contract\DepartureInterface;
use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use App\Entity\User;
use App\Form\DossierPersonal\DepartureType;
use App\Repository\DossierPersonal\DepartureRepository;
use App\Repository\DossierPersonal\PersonalRepository;
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
    private PersonalRepository $personalRepository;


    public function __construct(
        DepartureRepository                 $departureRepository,
        DepartServices                      $departServices,
        PersonalRepository                  $personalRepository,
        private readonly DepartureInterface $departureInterface,
        private readonly SalaryInterface    $salaryInterface,
        private readonly UtimeDepartServ    $utimeDepartServ,
    )
    {
        $this->departureRepository = $departureRepository;
        $this->departServices = $departServices;
        $this->personalRepository = $personalRepository;
    }

    /**
     * @throws Exception
     */
    #[Route('/api/depart', name: 'api2', methods: ['GET'])]
    public function apiDepart(): JsonResponse
    {
        $index = 0;
        $apiDeparture = [];

        $personals = $this->personalRepository->findDisablePersonal();


        foreach ($personals as $personal) {

            $apiDeparture[] = [
                'index' => ++$index,
                'full_name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
                'categorie' => $personal->getCategorie()->getCategorySalarie()->getName(),
                'fonction' => $personal->getJob()->getName(),
                'type_contract' => $personal->getContract()->getTypeContrat(),
                'date_embauche' => $personal->getContract()->getDateEmbauche(),
                'uuid' => $personal->getUuid()

            ];
        }

        return new JsonResponse($apiDeparture);
    }

    #[Route('/api/{typeDepart}/depart', name: 'api', methods: ['GET'])]
    public function apiDepartType($typeDepart): JsonResponse
    {
        $codeDepart = Status::REASONCODE[$typeDepart];

        $apiDeparture = [];
        $departures = $this->departureRepository->getDepartureByDate($codeDepart);
        foreach ($departures as $count => $departure) {
            $time_preavis = $this->utimeDepartServ->getTimePreavis((int)$departure['older'], $departure['intitule']);
            $url_solde = $this->generateUrl('departure_sold_of_all_compte', ['uuid' => $departure['uuid']]);
            $url_certificat = $this->generateUrl('departure_certificat_travail', ['uuid' => $departure['uuid']]);
            $apiDeparture[] = [
                'index' => ++$count,
                'nom_salarie' => $departure['firstName'],
                'prenom_salarie' => $departure['lastName'],
                'job_salarie' => $departure['job_name'],
                'workplace_salarie' => $departure['workplace_name'],
                'embauche_salarie' => $departure['date_embauche']->format('d/m/Y'),
                'date_depart_salarie' => $departure['departure_date']->format('d/m/Y'),
                'nbr_jour_presence' => $departure['day_of_presence'],
                'salaire_presence' => $departure['salaire_presence'],
                'gratification_prorata' => $departure['gratification_prorata'],
                'date_retour_conges' => $departure['date_retour_conge'] ? $departure['date_retour_conge']->format('d/m/Y') : $departure['date_embauche']->format('d/m/Y'),
                'periode_reference' => $departure['periode_references'],
                'jour_ouvrable_conge' => $departure['conges_ouvrable'],
                'salaire_moyen_conges' => $departure['salaire_moyen_conges'],
                'allocation_conge' => $departure['conges_amount'],
                'duree_preavis' => $time_preavis,
                'indemnite_preavis' => $departure['indemnite_preavis'],
                'salaire_global_moyen' => $departure['salaire_global_moyen'],
                'indemnite_licenciement' => (int)$departure['indemnite_licenciement'],
                'quotite_imposable_licenciement' => (int)$departure['quotite_imposable'],
                'quotite_non_imposable_licenciement' => (int)$departure['quotite_non_imposable'],
                'total_indemnite_imposable' => (int)$departure['total_indemnite_imposable'],
                'charge_personal_indemnite' => (int)$departure['total_charge_personal'],
                'net_payer_indemnite' => (int)$departure['net_payer_indemnite'],
                'frais_funeraire' => (int)$departure['frais_funeraire'],
                'url_solde_all_compte' => $url_solde,
                'url_certificat' => $url_certificat,
            ];
        }

        return new JsonResponse($apiDeparture);
    }

    #[Route('/', name: 'index2', methods: ['GET'])]
    public function index(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/departure/index2.html.twig', [
            'date' => $date,
        ]);
    }

    #[Route('/{typeDepart}/index', name: 'index', methods: ['GET'])]
    public function indexTypeDepart($typeDepart): Response
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
         * @var User $currentUser
         */

        $typeDeparts = [
            'demission' => 'Demission',
            'retraite' => 'Retraite',
            'licenciement_lourde' => 'Licenciement faute lourde',
            'licenciement_simple' => 'Licenciement faute simple',
            'deces' => 'Deces',
        ];

        $currentUser = $this->getUser();
        $departure = new Departure();
        $departure->setReason($typeDeparts[$typeDepart]);
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departure
                ->setReasonCode(strtoupper(Status::REASONCODE[$typeDepart]))
                ->setOlderPersonal($departure->getPersonal()->getOlder())
                ->setUser($currentUser);
            $this->salaryInterface->chargPersonalOut($departure);
            $this->salaryInterface->chargEmployerOut($departure);
            $this->departureInterface->departurePersonalCharge($departure);
            $this->departureInterface->departureEmployeurCharge($departure);
            $this->departureInterface->droitIndemnityByDeparture($departure);
            $manager->persist($departure);
            $manager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', ['typeDepart' => $typeDepart], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
            'typeDepart' => $typeDepart,
        ]);
    }


    #[Route('/new/{uuid}/{typeDepart}', name: 'new_uuid_typeDepart', methods: ['GET', 'POST'])]
    public function newByPersonal(Request $request, EntityManagerInterface $manager, Personal $personal, $typeDepart): Response
    {
        /**
         * @var User $currentUser
         */

        $typeDeparts = [
            'demission' => 'Démission',
            'retraite' => 'Retraite',
            'licenciement_lourde' => 'Licenciement faute lourde',
            'licenciement_simple' => 'Licenciement faute simple',
            'deces' => 'Décès',
        ];

        $currentUser = $this->getUser();
        $departure = new Departure();
        $departure->setReason($typeDeparts[$typeDepart]);
        $departure->setPersonal($personal);

        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departure->setReasonCode(Status::REASONCODE[$typeDepart]);
            $this->departServices->calculeDroitsAndIndemnity($departure);
            $departure->setUser($currentUser);
            $manager->persist($departure);
            //$manager->flush();

            flash()->addSuccess('Depart enregistrer avec succès.');
            return $this->redirectToRoute('departure_index', ['typeDepart' => $typeDepart], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/new.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
            'typeDepart' => $typeDepart,
        ]);
    }

    #[Route('/show/{uuid}/', name: 'show', methods: ['GET', 'POST'])]
    public function show(Departure $departure): Response {
        $reason = $departure->getReason();
        $departures = $this->departureRepository->getDepartureByDate($reason);
        return $this->render('dossier_personal/departure/show.html.twig', [
            'departure' => $departures,
        ]);
    }

    #[Route('/{uuid}/sold_of_all_compte', name: 'sold_of_all_compte', methods: ['GET'])]
    public function soldOfAllCompte(Departure $departure): Response
    {
        $departures = $this->departureRepository->getDepartureByDate($departure->getReason());
        $quotePartGratification = $indemniteCongeAmount = $indemniteLicenciement = null;
        $matricule = $full_name = $fonction = $service = $type_contrat = $embauche = $date_depart = null;
        foreach ($departures as $departure) {
            $quotePartGratification = $departure['gratification_prorata'];
            $indemniteCongeAmount = $departure['conges_amount'];
            $indemniteLicenciement = $departure['indemnite_licenciement'];
            $matricule = $departure['matricule'];
            $full_name = $departure['firstName'] . ' ' . $departure['lastName'];
            $fonction = $departure['job_name'];
            $service = $departure['workplace_name'];
            $type_contrat = $departure['type_contrat'];
            $embauche = $departure['date_embauche'];
            $date_depart = $departure['departure_date'];
        }



        return $this->render('dossier_personal/departure/sold.html.twig', [
            'gratification' => (int)$quotePartGratification,
            'conge_amount' => (int)$indemniteCongeAmount,
            'licenciement_amount' => (int)$indemniteLicenciement,
            'matricule' => $matricule,
            'full_name' => $full_name,
            'fonction' => $fonction,
            'service' => $service,
            'type_contrat' => $type_contrat,
            'embauche' => $embauche->format('d/m/Y'),
            'date_depart' => $date_depart->format('d/m/Y'),
        ]);
    }

    #[Route('/uuid/certificat_travail', name: 'certificat_travail', methods: ['GET'])]
    public function certificateTravail(): Response
    {
        return $this->render('dossier_personal/departure/certificate.html.twig');
    }
}
