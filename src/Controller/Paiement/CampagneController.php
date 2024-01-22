<?php

namespace App\Controller\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Form\Paiement\CampagneExcepType;
use App\Form\Paiement\CampagneType;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Service\PayrollService;
use App\Utils\Status;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/campagne', name: 'campagne_')]
class CampagneController extends AbstractController
{

    private PayrollService $payrollService;
    private PayrollRepository $payrollRepository;
    private CampagneRepository $campagneRepository;
    private CategoryChargeRepository $categoryChargeRepository;
    private HeureSupRepository $heureSupRepository;

    /**
     * @param PayrollService $payrollService
     * @param PayrollRepository $payrollRepository
     * @param CampagneRepository $campagneRepository
     * @param CategoryChargeRepository $categoryChargeRepository
     * @param HeureSupRepository $heureSupRepository
     */
    public function __construct(
        PayrollService           $payrollService,
        PayrollRepository        $payrollRepository,
        CampagneRepository       $campagneRepository,
        CategoryChargeRepository $categoryChargeRepository,
        HeureSupRepository       $heureSupRepository
    )
    {
        $this->payrollService = $payrollService;
        $this->payrollRepository = $payrollRepository;
        $this->campagneRepository = $campagneRepository;
        $this->categoryChargeRepository = $categoryChargeRepository;
        $this->heureSupRepository = $heureSupRepository;
    }

    #[Route('/index', name: 'livre', methods: ['GET'])]
    public function index(): Response
    {
        $payBooks = $this->payrollRepository->findPayrollByCampaign(true);
        return $this->render('paiement/campagne/pay_book.html.twig', [
            'payBooks' => $payBooks
        ]);
    }

    #[Route('/api/pay_book/', name: 'pay_book', methods: ['GET'])]
    public function getPayBook(): JsonResponse
    {
        $payroll = $this->payrollRepository->findPayrollByCampaign(true);
        $payBookData = [];
        foreach ($payroll as $item) {
            $payBookData[] = [
                'type_campagne' => $item->getCampagne()->isOrdinary() ? 'Ordinaire' : 'Exceptionnelle',
                /**
                 * element en rapport avec le salarié
                 */
                'matricule' => $item->getMatricule(),
                'full_name_salaried' => $item->getPersonal()->getFirstName() . ' ' . $item->getPersonal()->getLastName(),
                'service' => $item->getService(),
                'category_salaried' => $item->getCategories(),
                'number_part_salaried' => $item->getNumberPart(),
                'date_embauche' => date_format($item->getDateEmbauche(), 'd/m/Y'),
                'num_cnps' => $item->getNumCnps(),
                'salaire_base_salaried' => $item->getBaseAmount(),
                'sursalaire_salaried' => $item->getSursalaire(),
                'majoration_heurs_supp' => $item->getMajorationAmount(),
                'conge_payes' => $item->getCongesPayesAmount(),
                'prime_anciennete' => $item->getAncienneteAmount(),
                'prime_transport_imposable' => $item->getAmountTransImposable(),
                'avantage_imposable' => $item->getAmountAvantageImposable(),
                'prime_fonction' => $item->getPrimeFonctionAmount(),
                'prime_logement' => $item->getPrimeLogementAmount(),
                'indemnite_fonction' => $item->getIndemniteFonctionAmount(),
                'indemnite_logement' => $item->getIndemniteLogementAmount(),
                'salaire_brut_salaried' => $item->getBrutAmount(),
                'salaire_imposable_salaried' => $item->getImposableAmount(),
                'its_salaried' => $item->getSalaryIts(),
                'cnps_salaried' => $item->getSalaryCnps(),
                'cmu_salaried' => $item->getSalaryCmu(),
                'charge_salarial' => $item->getFixcalAmount(),
                'prime_transport_legal' => $item->getSalaryTransport(),
                'net_payer_salaried' => $item->getNetPayer(),
                /**
                 * element en rapport avec l'employeur
                 */
                'employer_is' => $item->getEmployeurIs(),
                'employer_cr' => $item->getEmployeurCr(),
                'employer_cmu' => $item->getEmployeurCmu(),
                'employer_pr' => $item->getEmployeurPf(),
                'employer_at' => $item->getEmployeurAt(),
                'employer_cnps' => $item->getEmployeurCnps(),
                'charge_patronal' => $item->getFixcalAmountEmployeur(),
                /**
                 * Masse de salaire global du salarié
                 */
                'masse_salariale' => $item->getMasseSalary(),
                'print_bulletin' => $this->generateUrl('campagne_make_bulletin', ['uuid' => $item->getPersonal()->getUuid()])
            ];
        }
        return new JsonResponse($payBookData);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/paiement/campagne/open', name: 'open_campagne', methods: ['GET', 'POST'])]
    public function open(Request $request, EntityManagerInterface $manager): Response
    {

        $ordinaryCampagne = $this->campagneRepository->getOrdinaryCampagne();
        if ($ordinaryCampagne) {
            $this->addFlash('error', 'Une campagne est déjà en cours !');
            return $this->redirectToRoute('campagne_livre');
        }

        $campagne = new Campagne();
        $lastCampagne = $this->getDetailOfLastCampagne($campagne);

        $form = $this->createForm(CampagneType::class, $campagne);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $form->get('personal')->getData();
            foreach ($personal as $item) {
                $this->payrollService->setPayroll($item, $campagne);
            }
            $campagne
                ->setActive(true)
                ->setOrdinary(true);
            $manager->persist($campagne);
            $manager->flush();
            flash()->addSuccess('Campagne ouverte avec succès.');
            return $this->redirectToRoute('campagne_livre');
        }

        return $this->render('paiement/campagne/open.html.twig', [
            'form' => $form->createView(),
            'campagne' => $campagne,
            'lastCampagne' => $lastCampagne
        ]);

    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/paiement/campagne/exceptional/open', name: 'open_campagne_exceptional', methods: ['GET', 'POST'])]
    public function openCampagneExcept(Request $request, EntityManagerInterface $manager): Response
    {

        $exceptionalCampagne = $this->campagneRepository->getExceptionalCampagne();
        if ($exceptionalCampagne) {
            $this->addFlash('error', 'Une campagne exceptionnelle est déjà en cours !');
            return $this->redirectToRoute('campagne_livre');
        }

        $campagne = new Campagne();
        $lastCampagne = $this->getDetailOfLastCampagne($campagne);

        $form = $this->createForm(CampagneExcepType::class, $campagne);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $form->get('personal')->getData();
            foreach ($personal as $item) {
                $this->payrollService->setPayroll($item, $campagne);
            }
            $campagne
                ->setActive(true)
                ->setOrdinary(false);
            $manager->persist($campagne);
            dd($campagne);
            //$manager->flush();
            flash()->addSuccess('Campagne ouverte avec succès.');
            return $this->redirectToRoute('campagne_livre');
        }

        return $this->render('paiement/campagne_exceptionnelle/open.html.twig', [
            'form' => $form->createView(),
            'campagne' => $campagne,
            'lastCampagne' => $lastCampagne
        ]);
    }

    public function getDetailOfLastCampagne(Campagne $campagne): array
    {
        $nbPersonal = 0;
        $salaireTotal = 0;
        $totalChargePersonal = 0;
        $totalChargeEmployeur = 0;
        $lastCampagne = $this->campagneRepository->lastCampagne();
        if ($lastCampagne) {
            $campagne->setLastCampagne($lastCampagne);
            $nbPersonal = $lastCampagne->getPersonal()->count();

            // Récupération de la somme des charge globals pour l'employeur et l'employé et aussi de la somme global des salaire brut
            $personnalFromLastCampagne = $lastCampagne->getPersonal();
            foreach ($personnalFromLastCampagne as $item) {
                $chargePersonals = $item->getChargePersonals();
                $chargeEmployeurs = $item->getChargeEmployeurs();
                $salaireTotal += $item->getSalary()->getBrutAmount();
                foreach ($chargePersonals as $chargePersonal) {
                    $totalChargePersonal += $chargePersonal->getAmountTotalChargePersonal();
                }
                foreach ($chargeEmployeurs as $chargeEmployeur) {
                    $totalChargeEmployeur += $chargeEmployeur->getTotalChargeEmployeur();
                }
            }

        }
        return [
            "nombre_personal" => $nbPersonal,
            "global_salaire_brut" => $salaireTotal,
            "global_charge_personal" => $totalChargePersonal,
            "global_charge_employeur" => $totalChargeEmployeur
        ];
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/paiement/campagne/close', name: 'close', methods: ['GET', 'POST'])]
    public function closeCampagne(EntityManagerInterface $manager, CampagneRepository $campagneRepository): Response
    {
        $campagneActives = $campagneRepository->getCampagneActives();

        if (!$campagneActives) {
            $this->addFlash('error', 'Aucune campagne ouverte au préalable');
            return $this->redirectToRoute('app_home');
        }

        foreach ($campagneActives as $campagneActive) {
            $campagneActive->setClosedAt(new DateTime());
            $campagneActive->setActive(false);
        }

        $manager->flush();
        $this->addFlash('success', 'Campagne fermée avec succès');
        return $this->redirectToRoute('app_home');

    }

    /**
     * @param Personal $personal
     * @return Response
     */
    #[Route('/bulletin/{uuid}', name: 'make_bulletin', methods: ['GET'])]
    public function makeBulletin(
        Personal $personal
    ): Response
    {
        $payrolls = $this->payrollRepository->findBulletinByCampaign(true, $personal);
        foreach ($payrolls as $index => $payroll) {
            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();
            $carbon = new Carbon();
            $nbHeureSupp = $this->heureSupRepository->getNbHeursSupp($personal, $carbon->month, $carbon->year);
            $nbHeure = 0;
            foreach ($nbHeureSupp as $item) {
                $nbHeure += $item?->getTotalHorraire();
            }
            $accountNumber = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
            }
            $dataPayroll = [
                'index' => ++$index,
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'grade_categoriel' => $payroll->getCategories(),
                'embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'number_part' => number_format($payroll->getNumberPart(), 1, ',', ' '),
                'numeroCnps' => $payroll->getNumCnps(),
                'periode' => $carbon->monthName . ' ' . $carbon->year,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'fullName_salaried' => $payroll->getPersonal()->getFirstName() . ' ' . $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                'salaire_base' => number_format($payroll->getBaseAmount(), 2, ',', ' '),
                'sursalaire' => number_format($payroll->getSursalaire(), 2, ',', ' '),
                'majoration_heure_sup' => number_format($payroll->getMajorationAmount(), 2, ',', ' '),
                'transport_imposable' => number_format($payroll->getAmountTransImposable(), 2, ',', ' '),
                'avantage_imposable' => number_format($payroll->getAmountAvantageImposable(), 2, ',', ' '),
                'prime_fonction' => number_format($payroll->getPrimeFonctionAmount(), 2, ',', ' '),
                'prime_logement' => number_format($payroll->getPrimeLogementAmount(), 2, ',', ' '),
                'indemnite_fonction' => number_format($payroll->getIndemniteFonctionAmount(), 2, ',', ' '),
                'indemnite_logement' => number_format($payroll->getIndemniteLogementAmount(), 2, ',', ' '),
                'total_brut' => number_format($payroll->getImposableAmount(), 2, ',', ' '),
                'amount_its_salarial' => number_format($payroll->getSalaryIts(), 2, ',', ' '),
                'taux_cnps_salarial' => number_format($tauxCnpsSalarial, 2, ',', ' '),
                'amount_cnps_salarial' => number_format($payroll->getSalaryCnps(), 2, ',', ' '),
                'taux_cr_employeur' => number_format($tauxCrEmployeur, 2, ',', ' '),
                'amount_cr_employeur' => number_format($payroll->getEmployeurCr(), 2, ',', ' '),
                'taux_pf_employeur' => number_format($tauxPfEmployeur, 2, ',', ' '),
                'amount_pf_employeur' => number_format($payroll->getEmployeurPf(), 2, ',', ' '),
                'taux_at_employeur' => number_format($tauxAtEmployeur, 2, ',', ' '),
                'amount_at_employeur' => number_format($payroll->getEmployeurAt(), 2, ',', ' '),
                'taux_is_employeur' => number_format($tauxIsEmployeur, 2, ',', ' '),
                'amount_is_employeur' => number_format($payroll->getEmployeurIs(), 2, ',', ' '),
                'taux_ta_employeur' => number_format($tauxTaEmployeur, 2, ',', ' '),
                'amount_ta_employeur' => number_format($payroll->getAmountTA(), 2, ',', ' '),
                'taux_fpc_employeur' => number_format($tauxFPCEmployeur, 2, ',', ' '),
                'amount_fpc_employeur' => number_format($payroll->getAmountFPC(), 2, ',', ' '),
                'taux_fpc_annuel_employeur' => number_format($tauxFPCAnnuelEmployeur, 2, ',', ' '),
                'amount_fpc_annuel_employeur' => number_format($payroll->getAmountAnnuelFPC(), 2, ',', ' '),
                'amount_cmu_salarial' => number_format($payroll->getSalaryCmu(), 2, ',', ' '),
                'amount_cmu_patronal' => number_format($payroll->getEmployeurCmu(), 2, ',', ' '),
                'charge_salarial' => number_format($payroll->getFixcalAmount(), 2, ',', ' '),
                'charge_patronal' => number_format($payroll->getFixcalAmountEmployeur(), 2, ',', ' '),
                'prime_transport' => number_format($payroll->getSalaryTransport(), 2, ',', ' '),
                'amount_prime_panier' => number_format($payroll->getAmountPrimePanier(), 2, ',', ' '),
                'amount_prime_salissure' => number_format($payroll->getAmountPrimeSalissure(), 2, ',', ' '),
                'amount_prime_tt' => number_format($payroll->getAmountPrimeTenueTrav(), 2, ',', ' '),
                'amount_prime_outi' => number_format($payroll->getAmountPrimeOutillage(), 2, ',', ' '),
                'amount_prime_rendement' => number_format($payroll->getAmountPrimeRendement(), 2, ',', ' '),
                'salaire_brut' => number_format($payroll->getBrutAmount(), 2, ',', ' '),
                'amount_avantage' => number_format($payroll->getAventageNonImposable(), 2, ',', ' '),
                'net_imposable' => number_format($payroll->getImposableAmount(), 2, ',', ' '),
                'heure_travailler' => number_format(Status::TAUX_HEURE, 0, ',', ' '),
                'nb_heure_supp' => number_format($nbHeure, 0, ',', ' '),
                'net_payes' => number_format($payroll->getNetPayer(), 2, ',', ' '),
                'mode_paiement' => $payroll->getPersonal()->getModePaiement(),
                'num_compte' => $accountNumber
            ];
        }
        return $this->render('test/test/index.html.twig', [
            'payrolls' => $dataPayroll
        ]);
    }

    #[Route('/alert/campagne/progess', name: 'alert_progess', methods: ['GET'])]
    public function campagneProgess()
    {
        $this->addFlash('error', 'Une camapagne est en cours');
        return $this->redirectToRoute('app_home');
    }
}