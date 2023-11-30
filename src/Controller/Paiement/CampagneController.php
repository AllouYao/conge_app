<?php

namespace App\Controller\Paiement;

use App\Entity\Paiement\Campagne;
use App\Form\Paiement\CampagneType;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Service\PayrollService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use JetBrains\PhpStorm\NoReturn;
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

    /**
     * @param PayrollService $payrollService
     * @param PayrollRepository $payrollRepository
     */
    public function __construct(PayrollService $payrollService, PayrollRepository $payrollRepository, CampagneRepository $campagneRepository)
    {
        $this->payrollService = $payrollService;
        $this->payrollRepository = $payrollRepository;
        $this->campagneRepository = $campagneRepository;
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

        $active = $this->campagneRepository->active();
        if ($active) {
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
     * @throws NoResultException
     */
    #[NoReturn] #[Route('/paiement/campagne/close', name: 'close', methods: ['GET', 'POST'])]
    public function closeCampagne(EntityManagerInterface $manager, CampagneRepository $campagneRepository): Response
    {

        $campagneActive = $campagneRepository->active();

        if (!$campagneActive) {
            $this->addFlash('error', 'Aucune campagne ouverte au préalable');
            return $this->redirectToRoute('app_home');
        }

        $campagneActive->setClosedAt(new DateTime());
        $campagneActive->setActive(false);

        $manager->flush();
        $this->addFlash('success', 'Campagne fermée avec succès');
        return $this->redirectToRoute('app_home');
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

}