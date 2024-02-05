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
                'employer_ta' => $item->getAmountTA(),
                'employer_fpc' => $item->getAmountFPC(),
                'employer_fpc_annuel' => $item->getAmountAnnuelFPC(),
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
            $JourNormalOrFerie = null;
            $jourOrNuit = null;
            $amountHeureSup15 = $amountHeureSup50 = $amountHeureSup75A = $amountHeureSup75B = $amountHeureSup100 = null;
            foreach ($nbHeureSupp as $item) {
                $nbHeure += $item?->getTotalHorraire();
                $jourOrNuit = $item->getTypeJourOrNuit();
                $JourNormalOrFerie = $item->getTypeDay();
            }

            if ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $nbHeure <= 6) {
                // 15% jour normal ~ 115%
                $amountHeureSup15 = $payroll->getMajorationAmount();
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::JOUR && $nbHeure > 6) {
                // 50% jour normal ~ 150%
                $amountHeureSup50 = $payroll->getMajorationAmount();
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::JOUR) {
                // 75% jour ferié or dimanche jour ~ 175%
                $amountHeureSup75A = $payroll->getMajorationAmount();
            } elseif ($JourNormalOrFerie == Status::NORMAL && $jourOrNuit == Status::NUIT) {
                // 75% jour normal or dimanche nuit ~ 175%
                $amountHeureSup75B = $payroll->getMajorationAmount();
            } elseif ($JourNormalOrFerie == Status::DIMANCHE_FERIE && $jourOrNuit == Status::NUIT) {
                // 100% jour ferié et dimanche nuit ~ 200%
                $amountHeureSup100 = $payroll->getMajorationAmount();
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
                'salaire_base' => $payroll->getBaseAmount(),
                'sursalaire' => $payroll->getSursalaire(),
                'majoration_heure_sup_15' => $amountHeureSup15,
                'majoration_heure_sup_50' => $amountHeureSup50,
                'majoration_heure_sup_75_A' => $amountHeureSup75A,
                'majoration_heure_sup_75_B' => $amountHeureSup75B,
                'majoration_heure_sup_100' => $amountHeureSup100,
                'transport_imposable' => (double)$payroll->getAmountTransImposable(),
                'avantage_imposable' => (double)$payroll->getAmountAvantageImposable(),
                'prime_fonction' => (double)$payroll->getPrimeFonctionAmount(),
                'prime_logement' => (double)$payroll->getPrimeLogementAmount(),
                'indemnite_fonction' => (double)$payroll->getIndemniteFonctionAmount(),
                'indemnite_logement' => (double)$payroll->getIndemniteLogementAmount(),
                'total_brut' => (double)$payroll->getImposableAmount(),
                'amount_its_salarial' => (double)$payroll->getSalaryIts(),
                'taux_cnps_salarial' => (double)$tauxCnpsSalarial,
                'amount_cnps_salarial' => (double)$payroll->getSalaryCnps(),
                'taux_cr_employeur' => (double)$tauxCrEmployeur,
                'amount_cr_employeur' => (double)$payroll->getEmployeurCr(),
                'taux_pf_employeur' => (double)$tauxPfEmployeur,
                'amount_pf_employeur' => (double)$payroll->getEmployeurPf(),
                'taux_at_employeur' => (double)$tauxAtEmployeur,
                'amount_at_employeur' => (double)$payroll->getEmployeurAt(),
                'taux_is_employeur' => (double)$tauxIsEmployeur,
                'amount_is_employeur' => (double)$payroll->getEmployeurIs(),
                'taux_ta_employeur' => (double)$tauxTaEmployeur,
                'amount_ta_employeur' => (double)$payroll->getAmountTA(),
                'taux_fpc_employeur' => (double)$tauxFPCEmployeur,
                'amount_fpc_employeur' => (double)$payroll->getAmountFPC(),
                'taux_fpc_annuel_employeur' => (double)$tauxFPCAnnuelEmployeur,
                'amount_fpc_annuel_employeur' => (double)$payroll->getAmountAnnuelFPC(),
                'amount_cmu_salarial' => (double)$payroll->getSalaryCmu(),
                'amount_cmu_patronal' => (double)$payroll->getEmployeurCmu(),
                'charge_salarial' => (double)$payroll->getFixcalAmount(),
                'charge_patronal' => (double)$payroll->getFixcalAmountEmployeur(),
                'prime_transport' => (double)$payroll->getSalaryTransport(),
                'amount_prime_panier' => (double)$payroll->getAmountPrimePanier(),
                'amount_prime_salissure' => (double)$payroll->getAmountPrimeSalissure(),
                'amount_prime_tt' => (double)$payroll->getAmountPrimeTenueTrav(),
                'amount_prime_outi' => (double)$payroll->getAmountPrimeOutillage(),
                'amount_prime_rendement' => (double)$payroll->getAmountPrimeRendement(),
                'salaire_brut' => (double)$payroll->getBrutAmount(),
                'amount_avantage' => (double)$payroll->getAventageNonImposable(),
                'net_imposable' => (double)$payroll->getImposableAmount(),
                'heure_travailler' => Status::TAUX_HEURE,
                'nb_heure_supp' => (double)$nbHeure,
                'net_payes' => (double)$payroll->getNetPayer(),
                'mode_paiement' => $payroll->getPersonal()->getModePaiement(),
                'num_compte' => $accountNumber,
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
                'preavis_amount' => (double)$payroll->getPreavisAmount(),
                'licenciement_imposable' => (double)$payroll->getLicemciementImposable(),
                'licenciement_ni' => (double)$payroll->getPersonal()->getDepartures()?->getDissmissalAmount() - (double)$payroll->getLicemciementImposable(),
                'gratification_depart' => (double)$payroll->getGratificationD(),
                'allocation_depart' => (double)$payroll->getAllocationCongeD(),
                'prime_anciennete' => (double)$payroll->getAncienneteAmount()
            ];
        }
        return $this->render('test/test/index.html.twig', [
            'payrolls' => $dataPayroll,
        ]);
    }

    #[Route('/alert/campagne/progess', name: 'alert_progess', methods: ['GET'])]
    public function campagneProgess()
    {
        $this->addFlash('error', 'Une camapagne est en cours');
        return $this->redirectToRoute('app_home');
    }
}