<?php

namespace App\Controller\Paiement;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Form\Paiement\CampagneExcepType;
use App\Form\Paiement\CampagneType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\EtatService;
use App\Service\HeureSupService;
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
    private EtatService $etatService;
    private HeureSupService $heureSupService;
    private CongeRepository $congeRepository;
    private CategoryChargeRepository $categoryChargeRepository;

    /**
     * @param PayrollService $payrollService
     * @param PayrollRepository $payrollRepository
     * @param CampagneRepository $campagneRepository
     * @param EtatService $etatService
     * @param HeureSupService $heureSupService
     * @param CongeRepository $congeRepository
     * @param CategoryChargeRepository $categoryChargeRepository
     */
    public function __construct(
        PayrollService           $payrollService,
        PayrollRepository        $payrollRepository,
        CampagneRepository       $campagneRepository,
        EtatService              $etatService,
        HeureSupService          $heureSupService,
        CongeRepository          $congeRepository,
        CategoryChargeRepository $categoryChargeRepository
    )
    {
        $this->payrollService = $payrollService;
        $this->payrollRepository = $payrollRepository;
        $this->campagneRepository = $campagneRepository;
        $this->etatService = $etatService;
        $this->heureSupService = $heureSupService;
        $this->congeRepository = $congeRepository;
        $this->categoryChargeRepository = $categoryChargeRepository;
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
                /**
                 * element en rapport avec le salarié
                 */
                'full_name_salaried' => $item['first_name'] . ' ' . $item['last_name'],
                'category_salaried' => $item['categories_name'],
                'number_part_salaried' => $item['nombre_part'],
                'salaire_base_salaried' => $item['base_salary'],
                'sursalaire_salaried' => $item['sursalaire'],
                'salaire_brut_salaried' => $item['brut_salary'],
                'salaire_imposable_salaried' => $item['imposable_salary'],
                'its_salaried' => $item['salary_its'],
                'cnps_salaried' => $item['salary_cnps'],
                'cmu_salaried' => $item['salary_cmu'],
                'assurance_salaried' => $item['salary_assurance'],
                'total_fixcal_salaried' => $item['salary_transport'],
                'transport_salaried' => $item['montant_fixcal_salary'],
                'net_payer_salaried' => $item['net_payer'],
                /**
                 * element en rapport avec l'employeur
                 */
                'employer_is' => $item['employeur_is'],
                'employer_fdfp' => $item['employeur_fdfp'],
                'employer_cr' => $item['employeur_cr'],
                'employer_cmu' => $item['employeur_cmu'],
                'employer_pr' => $item['employeur_pf'],
                'employer_at' => $item['employeur_at'],
                'employer_cnps' => $item['employeur_cnps'],
                'total_fixcal_employer' => $item['fixcal_amount_employeur'],
                'assurance_employer' => $item['employeur_assurance'],
                /**
                 * Masse de salaire global du salarié
                 */
                'masse_salariale' => $item['masse_salary'],
                'print_bulletin' => $this->generateUrl('campagne_make_bulletin',['id' => $item['personalId']])
                //'debuts' => $item['debut'] ? date_format($item['debut'], 'd/m/Y') : "",
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
                ->setActive(true);
            $campagne
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
                ->setActive(true);
            $campagne
                ->setOrdinary(false);
            $manager->persist($campagne);
            $manager->flush();
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

    #[Route('/bulletin/{id}', name: 'make_bulletin', methods: ['GET'])]
    public function makeBulletin(
        PersonalRepository $personalRepository,
        Personal $personal,
        PrimesRepository $primesRepository,
        DetailSalaryRepository $detailSalaryRepository
    ): Response
    {
        $primeSalissure = $primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
        $primeOutillage = $primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
        $primePanier = $primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
        $primeTt = $primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);

        $amountSalissure = $detailSalaryRepository->findOneBy(['prime' => $primeSalissure, 'salary' => $personal->getSalary()]);
        $amountOutillage = $detailSalaryRepository->findOneBy(['prime' => $primeOutillage, 'salary' => $personal->getSalary()]);
        $amountPanier = $detailSalaryRepository->findOneBy(['prime' => $primePanier, 'salary' => $personal->getSalary()]);
        $amountTt = $detailSalaryRepository->findOneBy(['prime' => $primeTt, 'salary' => $personal->getSalary()]);
        $data = [
            'fonction' => $personal->getFonction(),
            'departement' => $personal->getService(),
            'taux_its' => '0% à 32%',
            'acompte_avance_pret' => 0,
            'primePanier' => $amountPanier?->getAmountPrime() ?? 0,
            'primeOutil' => $amountOutillage?->getAmountPrime() ?? 0,
            'primeSalissure' => $amountSalissure?->getAmountPrime() ?? 0,
            'primeTt' => $amountTt?->getAmountPrime() ?? 0,
        ];
        $dataPayroll = [];
        $personal = $personalRepository->find($personal->getId());
        $payrolls = $this->payrollRepository->findBulletinByCampaign(true, $personal);
        $today = Carbon::now();
        foreach ($payrolls as $index => $payroll) {
            $nombreEnfant = $personal->getChargePeople()->count();
            $anciennete = ceil(($payroll['date_embauche']->diff($today)->y));
            $salaireHoraire = $payroll['base_salary'] / Status::TAUX_HEURE;
            $primeAnciennete = $this->etatService->getPrimeAnciennete($payroll['personal_id']);
            $amountHeureSupp = $this->heureSupService->getAmountHeursSuppByID($payroll['personal_id']);
            $gratification = $this->etatService->getGratification($payroll['personal_id']);
            $conges = $this->congeRepository->getLastCongeByID($payroll['personal_id']);
            $allocationConger = $conges?->getAllocationConge();
            $salaireBrut = $payroll['brut_salary'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $salaireImposable = $payroll['imposable_salary'] + $primeAnciennete + $amountHeureSupp + $gratification + $allocationConger;
            $categoryRateCNPS = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $amountCnpsPersonal = ($salaireImposable * $categoryRateCNPS) / 100;

            $dataPayroll = [
                'period_debut' => $payroll['debut'],
                'period_fin' => $payroll['fin'],
                'matricule' => $payroll['personal_matricule'],
                'index' => ++$index,
                'grade' => $payroll['categories_name'],
                'embauche' => $payroll['date_embauche'],
                'anciennete' => $anciennete,
                'numeroCnps' => $payroll['numero_cnps'],
                'categorie_salarie' => $payroll['categories_code'],
                'etat_civil' => $payroll['personal_etat_civil'],
                'nombre_enfant' => $nombreEnfant,
                'date_retour_dernier_conge' => $conges?->getDateDernierRetour()->format('d/m/Y') ?? '-',
                'fullName_salaried' => $payroll['first_name'] . ' ' . $payroll['last_name'],
                'nombre_jour_conge' => $conges?->getTotalDays() ,
                'date_depart_conge' => $conges?->getDateDepart()->format('d/m/Y'),
                'date_retour_conge' => $conges?->getDateRetour()->format('d/m/Y'),
                'taux_horaire' => Status::TAUX_HEURE,
                'salaire_horaire' => (int)$salaireHoraire,
                'salaire_base' => (int)$payroll['base_salary'],
                'prime_transport' => (int)$payroll['salary_transport'],
                'prime_anciennete' => (int)$primeAnciennete ?? 0,
                'autre_prime_indemnite' => (int)$payroll['prime_juridique'] ?? 0,
                'heure_supplementaire' => (int)$amountHeureSupp,
                'gratification' => (int)$gratification ?? 0,
                'conge_payes' => (int)$allocationConger ?? 0,
                'salaire_brut' => (int)$salaireBrut,
                'salaire_brut_imposable' => (int)$salaireImposable ?? 0,
                'taux_cnps' => $categoryRateCNPS,
                'cnps_salaried' => (int)$amountCnpsPersonal,
                'its_salaried' => (int)$payroll['personal_its'] ?? 0,
                'retenue_diverse' => (int)$payroll['salary_cmu'] + (int)$payroll['salary_assurance'],
                'cmu' => (int)$payroll['salary_cmu'],
                'assurance' => (int)$payroll['salary_assurance'],
                'total_retenue' => (int)$payroll['total_revenu_divers'],
                'charge_salariale' => (int)$payroll['montant_fixcal_salary'],
                'charge_patronal' => (int)$payroll['fixcal_amount_employeur'],
                'avantage_nature' => (int)$payroll['prime_logement'],
                'net_payer' => (int)$payroll['net_payer']
            ];
        }
        //dd($dataPayroll);
        return $this->render('paiement/campagne/bulletin.html.twig', [
            'data' => $data,
            'payrolls' => $dataPayroll
        ]);
    }

    #[Route('/bulletin/{id}', name: 'bulletin', methods: ['GET'])]
    public function bulletin(PersonalRepository $personalRepository, Personal $personal): void
    {
        $personal = $personalRepository->find($personal->getId());
        $payrolls = $this->payrollRepository->findBulletinByCampaign(false, $personal);
        if (!$payrolls) {
            throw $this->createNotFoundException('Employé non trouvé');
        }
        dd($personal, $payrolls);
    }
}