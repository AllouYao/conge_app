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
use App\Repository\Impots\CategoryChargeRepository;
use App\Service\UtimeDepartServ;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier/personal/departure', name: 'departure_')]
class DepartureController extends AbstractController
{
    private DepartureRepository $departureRepository;
    private PersonalRepository $personalRepository;


    public function __construct(
        DepartureRepository                       $departureRepository,
        PersonalRepository                        $personalRepository,
        private readonly DepartureInterface       $departureInterface,
        private readonly SalaryInterface          $salaryInterface,
        private readonly UtimeDepartServ          $utimeDepartServ,
        private readonly CategoryChargeRepository $categoryChargeRepository
    )
    {
        $this->departureRepository = $departureRepository;
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
        if (!$personals) {
            return $this->json(['data' => []]);
        }
        foreach ($personals as $personal) {
            $apiDeparture[] = [
                'index' => ++$index,
                'full_name' => $personal['firstName'] . ' ' . $personal['lastName'],
                'categorie' => $personal['category_name'],
                'fonction' => $personal['job_name'],
                'type_contract' => $personal['typeContrat'],
                'date_embauche' => $personal['dateEmbauche']->format('d/m/Y'),
                'uuid' => $personal['uuid']

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
        if (empty($departures)) {
            return $this->json([]);
        }
        foreach ($departures as $count => $departure) {
            $time_preavis = $this->utimeDepartServ->getTimePreavis((int)$departure['older'], $departure['intitule']);
            $url_solde = $this->generateUrl('departure_sold_of_all_compte', ['uuid' => $departure['uuid']]);
            $url_certificat = $this->generateUrl('departure_certificat_travail', ['uuid' => $departure['uuid']]);
            $link_show = $this->generateUrl('departure_show', ['uuid' => $departure['uuid']]);
            $link_bulletin = $this->generateUrl('departure_bulletin', ['uuid' => $departure['uuid']]);
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
                'modifier' => $link_show,
                'bulletin' => $link_bulletin
            ];
        }

        return new JsonResponse($apiDeparture);
    }

    #[Route('/', name: 'index2', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dossier_personal/departure/index2.html.twig');
    }

    #[Route('/{typeDepart}/index', name: 'index', methods: ['GET'])]
    public function indexTypeDepart($typeDepart): Response
    {
        return $this->render('dossier_personal/departure/index.html.twig', [
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
            'démissions' => 'Démission',
            'retraites' => 'Rétraite',
            'licenciements_faute_lourde' => 'Licenciement faute lourde',
            'licenciements_faute_simple' => 'Licenciement faute simple',
            'décès' => 'Décès',
        ];

        $currentUser = $this->getUser();
        $departure = new Departure();
        $departure->setReason($typeDeparts[$typeDepart]);
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departure
                ->setReasonCode(Status::REASONCODE[$typeDepart])
                ->setOlderPersonal($departure->getPersonal()->getOlder())
                ->setStatut(Status::PENDING)
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
            'démissions' => 'Démission',
            'retraites' => 'Rétraite',
            'licenciements_faute_lourde' => 'Licenciement faute lourde',
            'licenciements_faute_simple' => 'Licenciement faute simple',
            'décès' => 'Décès',
        ];

        $currentUser = $this->getUser();
        $departure = new Departure();
        $departure->setReason($typeDeparts[$typeDepart]);
        $departure->setPersonal($personal);

        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departure
                ->setReasonCode(Status::REASONCODE[$typeDepart])
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

    #[Route('/{uuid}/show', name: 'show', methods: ['GET', 'POST'])]
    public function show(Departure $departure): Response
    {
        $reason = $departure->getReason();
        $departure = $time_preavis = null;
        $departures = $this->departureRepository->getDepartureByDate($reason);
        foreach ($departures as $value) {
            $departure = $value;
            $time_preavis = $this->utimeDepartServ->getTimePreavis((int)$departure['older'], $departure['intitule']);
        }
        return $this->render('dossier_personal/departure/show.html.twig', [
            'departure' => $departure,
            'duree_preavis' => $time_preavis
        ]);
    }

    #[Route('/edit/{uuid}/{typeDepart}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $manager, $typeDepart, Departure $departure): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        $form = $this->createForm(DepartureType::class, $departure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departure
                ->setReasonCode(Status::REASONCODE[$typeDepart])
                ->setOlderPersonal($departure->getPersonal()->getOlder())
                ->setStatut(Status::PENDING)
                ->setUser($currentUser);
            $this->salaryInterface->chargPersonalOut($departure);
            $this->salaryInterface->chargEmployerOut($departure);
            $this->departureInterface->departurePersonalCharge($departure);
            $this->departureInterface->departureEmployeurCharge($departure);
            $this->departureInterface->droitIndemnityByDeparture($departure);
            $manager->persist($departure);
            $manager->flush();

            flash()->addSuccess('Depart modifier avec succès.');
            return $this->redirectToRoute('departure_index', ['typeDepart' => $typeDepart], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/departure/edit.html.twig', [
            'departure' => $departure,
            'form' => $form->createView(),
            'typeDepart' => $typeDepart,
        ]);
    }

    #[Route('/{uuid}/sold_of_all_compte', name: 'sold_of_all_compte', methods: ['GET', 'POST'])]
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

    #[Route('/{uuid}/certificat_travail', name: 'certificat_travail', methods: ['GET'])]
    public function certificateTravail(): Response
    {
        return $this->render('dossier_personal/departure/certificate.html.twig');
    }

    #[Route('/{uuid}/bulletin', name: 'bulletin', methods: ['GET'])]
    public function makeBulletin(Departure $departure)
    {
        $departures = $this->departureRepository->getDepartureByDate($departure->getReason());
        $time_preavis = null;
        $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
        $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
        $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
        $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
        $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
        $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
        $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
        $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();
        $accountBanque = $departure->getPersonal()->getAccountBanks();
        foreach ($accountBanque as $value) {
            $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
            $nameBanque = $value->getName();
        }
        $api_bulletin = [];
        foreach ($departures as $key => $value) {
            $time_preavis = $this->utimeDepartServ->getTimePreavis((int)$value['older'], $value['intitule']);
            $api_bulletin = [
                'matricule' => $value['matricule'],
                'workplace' => $value['workplace_name'],
                'categorie' => $value['intitule'],
                'nombre_part' => $value['nombre_part'],
                'date_embauche' => $value['date_embauche']->format('d/m/Y'),
                'numero_cnps' => $value['refCNPS'],
                'date_depart' => $value['departure_date']->format('d/m/Y'),
                'date_edition' => $value['createdAt']->format('d/m/Y'),
                'nom' => $value['firstName'],
                'prenom' => $value['lastName'],
                'service' => $value['job_name'],
                'mode_paiement' => $value['modePaiement'],
                'frais_funeraire' => $value['frais_funeraire'],
                'charge_salarial' => $value['total_charge_personal'],
                'charge_patronal' => $value['total_charge_employer'],
                'amount_cmu_salarial' => $value['amountCmu'],
                'amount_cmu_patronal' => $value['amountCmuE'],
                'total_brut' => $value['total_indemnite_imposable'],
                'amount_fpc_annuel_employeur' => $value['amountFpcYear'],
                'amount_fpc_employeur' => $value['amountfpc'],
                'amount_ta_employeur' => $value['amountTa'],
                'amount_is_employeur' => $value['amountIs'],
                'amount_at_employeur' => $value['amountAt'],
                'smig' => $value['smig'],
                'amount_pf_employeur' => $value['amountPf'],
                'amount_cr_employeur' => $value['amountCr'],
                'amount_cnps_salarial' => $value['amountCnps'],
                'amount_its_salarial' => $value['impotNet'],
                'nombre_jour_travailler' => $value['day_of_presence'],
                'salaire_presence' => $value['salaire_presence'],
                'gratification' => $value['gratification_prorata'],
                'indemnite_conge' => $value['conges_amount'],
                'indemnite_preavis' => $value['indemnite_preavis'],
                'indemnite_licenciement' => $value['quotite_imposable'],
                'day_conges' => $value['conges_ouvrable'],
                'time_preavis' => $time_preavis,
                'licenciement_not_impose' => $value['quotite_non_imposable'],
                'taux_cnps_salarial' => (float)$tauxCnpsSalarial,
                'taux_cr_employeur' => (float)$tauxCrEmployeur,
                'taux_pf_employeur' => (float)$tauxPfEmployeur,
                'taux_at_employeur' => (float)$tauxAtEmployeur,
                'taux_is_employeur' => (float)$tauxIsEmployeur,
                'taux_ta_employeur' => (float)$tauxTaEmployeur,
                'taux_fpc_employeur' => (float)$tauxFPCEmployeur,
                'taux_fpc_annuel_employeur' => (float)$tauxFPCAnnuelEmployeur,
                'taux_its' => '0 à 32 %',
                'indemnite_brut' => $value['salaire_presence'] + $value['gratification_prorata'] + $value['conges_amount'] + $value['indemnite_preavis'] + $value['dissmissalAmount'],
                'net_payes' => $value['net_payer_indemnite'],
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                'raison_depart' => $value['reason']
            ];
        }
        //dd($departures, $api_bulletin);
        return $this->render('dossier_personal/departure/bulletin.html.twig', [
            'payrolls' => $api_bulletin,
            'caisse' => Status::CAISSE,
            'virement' => Status::VIREMENT
        ]);
    }
}
