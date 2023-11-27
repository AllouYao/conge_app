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

    /**
     * @param PayrollService $payrollService
     * @param PayrollRepository $payrollRepository
     */
    public function __construct(PayrollService $payrollService, PayrollRepository $payrollRepository)
    {
        $this->payrollService = $payrollService;
        $this->payrollRepository = $payrollRepository;
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
    public function open(CampagneRepository $campagneRepository, Request $request, EntityManagerInterface $manager): Response
    {
        $nbPersonal = 0;
        $totalChargePersonal = 0;
        $totalChargeEmployeur = 0;
        $salaireTotal = 0;

        $active = $campagneRepository->active();
        if ($active) {
            flash()->addInfo('Une campagne est déjà en cours !');
            return $this->redirectToRoute('campagne_livre');
            //throw $this->createNotFoundException('Une campagne est déjà active.');
        }

        $campagne = new Campagne();
        $lastCampagne = $campagneRepository->lastCampagne();
        if ($lastCampagne) {
            $nbPersonal = $campagneRepository->getNbrePersonal($lastCampagne);
            $campagne->setLastCampagne($lastCampagne);

            // Récupération de la somme des charge globals pour l'employeur et l'employé et aussi de la somme global des salaire brut
            $personnalFromLastCampagne = $lastCampagne->getPersonal();
            foreach ($personnalFromLastCampagne as $item) {
                $chargePersonals = $item->getChargePersonals();
                $chargeEmployeurs = $item->getChargeEmployeurs();
                $salaireTotal = $item->getSalary()->getBrutAmount();
                foreach ($chargePersonals as $chargePersonal) {
                    $totalChargePersonal = $chargePersonal->getAmountTotalChargePersonal();
                }
                foreach ($chargeEmployeurs as $chargeEmployeur) {
                    $totalChargeEmployeur = $chargeEmployeur->getTotalChargeEmployeur();
                }
            }

        }

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
            'nombre_personal' => $nbPersonal,
            'campagne' => $campagne,
            'global_charge_personal' => $totalChargePersonal,
            'global_charge_employeur' => $totalChargeEmployeur,
            'global_salaire_brut' => $salaireTotal,
        ]);

    }

    #[Route('/paiement/campagne/{uuid}/close', name: 'close_campagne', methods: ['GET', 'POST'])]
    public function closeCampagne(Campagne $campagne, EntityManagerInterface $manager, CampagneRepository $campagneRepository): Response
    {

        $campagne = $campagneRepository->find($campagne->getUuid());
        if (!$campagne) {
            throw $this->createNotFoundException('Campagne non trouvée pour l\'id ' . $campagne->getUuid());
        }

        $lastActiveCampagne = $campagneRepository->active();

        if ($lastActiveCampagne && $lastActiveCampagne !== $campagne) {
            $lastActiveCampagne->setLastCampagne(null);
        }

        $campagne->setClosedAt(new DateTime());
        $campagne->setActive(false);

        // Mettre à jour la relation lastCampagne avec la nouvelle campagne active
        $lastActiveCampagne = $campagneRepository->findOneBy(['active' => true]);
        if ($lastActiveCampagne && $lastActiveCampagne !== $campagne) {
            $campagne->setLastCampagne($lastActiveCampagne);
        }

        $manager->flush();

        return $this->redirectToRoute('app_home', ['id' => $campagne->getId()]);
    }

}