<?php

namespace App\Controller\Paiement;

use Exception;
use IntlDateFormatter;
use App\Entity\Paiement\Campagne;
use App\Form\Paiement\CampagneType;
use App\Service\PayrollService;
use App\Utils\Status;
use DateTime;
use App\Entity\DossierPersonal\Personal;
use App\Form\Paiement\CampagneExcepType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\Paiement\PayrollRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\Paiement\CampagneRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/campagne', name: 'campagne_')]
class CampagneController extends AbstractController
{

    private PayrollService $payrollService;
    private PayrollRepository $payrollRepository;
    private CampagneRepository $campagneRepository;
    private CategoryChargeRepository $categoryChargeRepository;
    private HeureSupRepository $heureSupRepository;
    private CongeRepository $congeRepository;

    /**
     * @param PayrollService $payrollService
     * @param PayrollRepository $payrollRepository
     * @param CampagneRepository $campagneRepository
     * @param CategoryChargeRepository $categoryChargeRepository
     * @param HeureSupRepository $heureSupRepository
     * @param CongeRepository $congeRepository
     * @param EntityManagerInterface $manager
     */
    public function __construct(
        PayrollService                      $payrollService,
        PayrollRepository                   $payrollRepository,
        CampagneRepository                  $campagneRepository,
        CategoryChargeRepository            $categoryChargeRepository,
        HeureSupRepository                  $heureSupRepository,
        CongeRepository                     $congeRepository,
        private readonly EntityManagerInterface $manager
    )
    {
        $this->payrollService = $payrollService;
        $this->payrollRepository = $payrollRepository;
        $this->campagneRepository = $campagneRepository;
        $this->categoryChargeRepository = $categoryChargeRepository;
        $this->heureSupRepository = $heureSupRepository;
        $this->congeRepository = $congeRepository;
    }

    #[Route('/index', name: 'livre', methods: ['GET'])]
    public function index(): Response
    {
        $dateDebut = null;
        $dateFin = null;

        if ($this->isGranted('ROLE_RH')) {

            $payBooks = $this->payrollRepository->findPayrollByCampaign(true);

        } else {

            $payBooks = $this->payrollRepository->findPayrollByCampaignEmploye(true);

        }

        foreach ($payBooks as $book) {

            $dateDebut = $book->getCampagne()->getDateDebut();
            $dateFin = $book->getCampagne()->getDateFin();

        }
       $campagne = $this->campagneRepository->findCampagnActiveAndPending();

        return $this->render('paiement/campagne/pay_book.html.twig', [
            'date_debut' => $dateDebut ? date_format($dateDebut, 'd/m/Y') : ' ',
            'date_fin' => $dateFin ? date_format($dateFin, 'd/m/Y') : ' ',
            'campagne' => $campagne

        ]);
    }

    #[Route('/api/pay_book/', name: 'pay_book', methods: ['GET'])]
    public function getPayBook(): JsonResponse
    {
        if ($this->isGranted('ROLE_RH')) {
            $payroll = $this->payrollRepository->findPayrollByCampaign(true);
        } else {
            $payroll = $this->payrollRepository->findPayrollByCampaignEmploye(true);
        }
        $payBookData = [];
        foreach ($payroll as $index => $item) {
            $url = $this->generateUrl('campagne_bulletin_ordinaire', ['uuid' => $item->getPersonal()->getUuid()]);
            $payBookData[] = [
                'campagn_id' =>$item->getMatricule(),
                'index' => ++$index,
                'type_campagne' => $item->getCampagne()->isOrdinary() ? 'Ordinaire' : 'Exceptionnelle',
                'day_of_presence' => $item->getDayOfPresence(),
                /**
                 * element en rapport avec le salarié
                 */
                'matricule' => $item->getMatricule(),
                'full_name_salaried' => $item->getPersonal()->getFirstName() . ' ' . $item->getPersonal()->getLastName(),
                'service' => $item->getService(),
                'poste' => $item->getDepartement(),
                'category_salaried' => $item->getCategories(),
                'number_part_salaried' => $item->getNumberPart(),
                'date_embauche' => date_format($item->getDateEmbauche(), 'd/m/Y'),
                'num_cnps' => $item->getNumCnps(),
                'salaire_base_salaried' => $item->getBaseAmount(),
                'sursalaire_salaried' => $item->getSursalaire(),
                'majoration_heurs_supp' => $item->getMajorationAmount(),
                'conge_payes' => $item->getCongesPayesAmount(),
                'prime_anciennete' => $item->getAncienneteAmount(),
                'prime_de_tenue' => $item->getAmountPrimeTenueTrav(),
                'prime_de_salissure' => $item->getAmountPrimeSalissure(),
                'prime_outillage' => $item->getAmountPrimeOutillage(),
                'prime_panier' => $item->getAmountPrimePanier(),
                'prime_transport_imposable' => $item->getAmountTransImposable(),
                'avantage_imposable' => $item->getAmountAvantageImposable(),
                'prime_fonction' => $item->getPrimeFonctionAmount(),
                'prime_logement' => $item->getPrimeLogementAmount(),
                'indemnite_fonction' => $item->getIndemniteFonctionAmount(),
                'indemnite_logement' => $item->getIndemniteLogementAmount(),
                'salaire_brut_salaried' => $item->getBrutAmount(),
                'salaire_imposable_salaried' => $item->getImposableAmount(),
                'its_salaried' => $item->getSalaryIts(),
                'fixcale_salariale' => $item->getFixcalAmount(),
                'cnps_salaried' => $item->getSalaryCnps(),
                'cmu_salaried' => $item->getSalaryCmu(),
                'assurance_salariale' => $item->getSalarySante(),
                'charge_salarial' => $item->getTotalRetenueSalarie(),
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
                'amount_fdfp' => $item->getAmountTA() + $item->getAmountFPC() + $item->getAmountAnnuelFPC(),
                'assurance_patronales' => $item->getEmployeurSante(),
                'charge_patronal' => $item->getTotalRetenuePatronal(),
                /**
                 * Masse de salaire global du salarié
                 */
                'masse_salariale' => $item->getMasseSalary(),
                'print_bulletin' => $url,
                'regul_moins_percus' => $item->getRemboursNet() + $item->getRemboursBrut(),
                'regul_plus_percus' => $item->getRetenueNet() + $item->getRetenueBrut(),
                'amount_pret_mensuel' => $item->getAmountMensualityPret(),
                'amount_acompte_mensuel' => $item->getAmountMensuelAcompt()
            ];
        }
        return new JsonResponse($payBookData);
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    #[Route('/paiement/campagne/open', name: 'open_campagne', methods: ['GET', 'POST'])]
    public function openCompleteExercice(Request $request, EntityManagerInterface $manager): Response
    {
        // Pour le récapitulatif de la dernière paie valider et payer.
        $ordinaryCampagne = $this->campagneRepository->getOrdinaryCampagne();
        if ($ordinaryCampagne) {
            $this->addFlash('error', 'Une paie est déjà en cours d\'exécution. Merci de bien vouloir terminer ce procéssuce!');
            return $this->redirectToRoute('campagne_livre');
        }

        $campagne = new Campagne();

        // setter the value of dateDebut and dateFin in campagne entity
        $dateRequest = $request->request->get('dateDebut');
        if ($dateRequest) {
            $dateRequestObj = DateTime::createFromFormat('Y-m', $dateRequest);
            $dateDebut = $dateRequestObj->format('Y-m-01');
            $dateFin = $dateRequestObj->format('Y-m-t');
            $campagne->setDateDebut(new DateTime($dateDebut))->setDateFin(new DateTime($dateFin));
        }


        $lastCampagne = $this->getDetailOfLastCampagne($campagne, true);
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $periode = $lastCampagne['periode'];
        $date = $formatter->format($periode);

        $form = $this->createForm(CampagneType::class, $campagne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fullDate = new DateTime();
            $day = 1;
            $month = $fullDate->format('m');
            $year = $fullDate->format('Y');
            $dateOfMonth = new DateTime($day . '-' . $month . '-' . $year);
            $previousCampagne = $this->campagneRepository->findLast();
            $personals = $form->get('personal')->getData();
            $countPersonal = count($personals);

            if ($countPersonal > 0) {
                foreach ($personals as $personal) {
                    $dateEmbauche = $personal->getContract()->getDateEmbauche();
                    //personnel arrivé avant le debut de la campagne passée
                    if (($dateEmbauche > $previousCampagne?->getStartedAt()) && $previousCampagne) {
                        $this->payrollService->setExtraMonthPayroll($personal, $campagne);
                        //personnel arrivé au milieu de le du mois de la campagne en cours
                    } elseif (($dateEmbauche > $dateOfMonth) && $dateEmbauche < $campagne->getStartedAt()) {
                        flash()->addSuccess('personal prorata');
                        $this->payrollService->setProrataPayroll($personal, $campagne);
                    } elseif (!($dateEmbauche > $campagne->getStartedAt())) {
                        //personnel normal
                        flash()->addSuccess('personal normal');
                        $this->payrollService->setPayroll($personal, $campagne);
                    } else {
                        flash()->addInfo("Aucun salarié  n'est eligible pour participé à la paie de ce mois.");
                    }
                }
                $campagne
                    ->setActive(true)
                    ->setStatus(Status::PENDING)
                    ->setOrdinary(true);
                $manager->persist($campagne);
                $manager->flush();
                flash()->addSuccess('Paie ouverte avec succès.');
                return $this->redirectToRoute('campagne_livre');

            } else {
                flash()->addWarning('Aucun personnel sélectionné!');
                return $this->redirectToRoute('campagne_open_campagne');
            }
        }

        return $this->render('paiement/campagne/open.html.twig', [
            'form' => $form->createView(),
            'campagne' => $campagne,
            'lastCampagne' => $lastCampagne,
            'periode_paie' => $date,
            'today' => new DateTime()
        ]);

    }


    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    #[Route('/paiement/campagne/exceptional/open', name: 'open_campagne_exceptional', methods: ['GET', 'POST'])]
    public function openProrataExercice(Request $request, EntityManagerInterface $manager): Response
    {

        $exceptionalCampagne = $this->campagneRepository->getExceptionalCampagne();
        if ($exceptionalCampagne) {

            $this->addFlash('error', 'Une campagne exceptionnelle est déjà en cours !');
            return $this->redirectToRoute('campagne_livre');
        }

        $campagne = new Campagne();
        $lastCampagne = $this->getDetailOfLastCampagne($campagne, false);

        $form = $this->createForm(CampagneExcepType::class, $campagne);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $form->get('personal')->getData();
            foreach ($personal as $item) {

                $this->payrollService->setExeptionnelPayroll($item->getDepartures(), $campagne);
            }
            //foreach ($personal as $item) {//$this->payrollService->setExeptionnelPayroll($item->getDepartures(), $campagne);}
            $campagne
                ->setActive(true)
                ->setOrdinary(false);
            $manager->persist($campagne);
            $manager->flush();
            flash()->addSuccess('Campagne ouverte avec succès.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('paiement/campagne_exceptionnelle/open.html.twig', [
            'form' => $form->createView(),
            'campagne' => $campagne,
            'lastCampagne' => $lastCampagne
        ]);
    }

    public function getDetailOfLastCampagne(Campagne $campagne, bool $isOrdinaire): array
    {
        $nbPersonal = 0;
        $masseSalarie = 0;
        $totalChargePersonal = 0;
        $totalChargeEmployeur = 0;
        $totalChargeFiscalP = 0;
        $totalChargeSocialeP = 0;
        $totalChargeFiscalE = 0;
        $totalChargeSocialeE = 0;
        $periode = null;
        $lastCampagne = $this->campagneRepository->lastCampagne($isOrdinaire);
        if ($lastCampagne) {
            $campagne->setLastCampagne($lastCampagne);
            $nbPersonal = $lastCampagne->getPersonal()->count();
            $periode = $lastCampagne->getDateDebut();

            // Récupération de la somme des charge globals pour l'employeur et l'employé et aussi de la somme global des salaire brut
            $personnalFromLastCampagne = $lastCampagne->getPersonal();
            foreach ($personnalFromLastCampagne as $item) {
                $chargePersonals = $item->getChargePersonals();
                $chargeEmployeurs = $item->getChargeEmployeurs();
                $salaireTotal = $item->getPayrolls();
                foreach ($salaireTotal as $value) {
                    $masseSalarie += $value->getMasseSalary();
                }
                foreach ($chargePersonals as $chargePersonal) {
                    $totalChargePersonal += $chargePersonal->getAmountTotalChargePersonal();
                    $totalChargeFiscalP += $chargePersonal->getAmountIts();
                    $totalChargeSocialeP += $chargePersonal->getAmountCMU() + $chargePersonal->getAmountCNPS();
                }
                foreach ($chargeEmployeurs as $chargeEmployeur) {
                    $totalChargeEmployeur += $chargeEmployeur->getTotalChargeEmployeur();
                    $totalChargeFiscalE += $chargeEmployeur->getAmountIS() + $chargeEmployeur->getAmountTA() +
                        $chargeEmployeur->getAmountFPC() + $chargeEmployeur->getAmountAnnuelFPC();
                    $totalChargeSocialeE += $chargeEmployeur->getAmountCR() + $chargeEmployeur->getAmountPF() + $chargeEmployeur->getAmountAT();
                }
            }

        }
        return [
            "nombre_personal" => $nbPersonal,
            "global_salaire_brut" => $masseSalarie,
            "global_charge_personal" => $totalChargePersonal,
            "global_charge_employeur" => $totalChargeEmployeur,
            "fiscale_salariale" => $totalChargeFiscalP,
            "fiscale_patronale" => $totalChargeFiscalE,
            "sociale_salariale" => $totalChargeSocialeP,
            "sociale_patronale" => $totalChargeSocialeE,
            "periode" => $periode
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
            $campagneActive->setActive(false)->setStatus(Status::TERMINER);
            $payroll = $campagneActive->getPayrolls();
            foreach ($payroll as $item) {
                $item->setStatus(Status::PAYE);
            }
        }


        $manager->flush();
        $this->addFlash('success', 'Campagne fermée avec succès');
        return $this->redirectToRoute('app_home');

    }

    #[Route('/alert/campagne/progess', name: 'alert_progess', methods: ['GET'])]
    public function campagneProgess(): RedirectResponse
    {
        $this->addFlash('error', 'Une campagne est en cours');
        return $this->redirectToRoute('app_home');
    }

    /** Bulletin de paie pour les campagnes de paie ordinaire active */
    #[Route('/bulletin/ordinaire/{uuid}', name: 'bulletin_ordinaire', methods: ['GET'])]
    public function bulletin(Personal $personal): Response
    {

        $payrolls = $this->payrollRepository->findBulletinByCampaign(true, $personal);
        $dataPayroll = null;
        foreach ($payrolls as $payroll) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $periode = $payroll->getCampagne()->getDateDebut();
            $date = $formatter->format($periode);
            $accountNumber = null;
            $nameBanque = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
                $nameBanque = $value->getName();
            }

            $month = $periode->format('m');
            $year = $periode->format('Y');
            $nbHeureSupp = $this->heureSupRepository->getNbHeursSupp($personal, $month, $year);
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

            $personalConges = $payroll->getPersonal();
            $conges = $this->congeRepository->getLastCongeByID($personalConges->getId(), false);
            $dernierRetour = $conges?->getDateDernierRetour();

            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();

            $dataPayroll = [
                /** Information de congés */
                'date_dernier_conges' => $dernierRetour,
                'nombre_jour_travailler' => $payroll->getDayOfPresence(),
                /** Element en rapport avec le personal */
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'categorie' => $payroll->getCategories(),
                'Salaire_categoriel' => $payroll->getPersonal()->getCategorie()->getAmount(),
                'nombre_part' => $payroll->getNumberPart(),
                'date_embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'numero_cnps' => $payroll->getNumCnps(),
                'periode_paie' => $date,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'nom' => $payroll->getPersonal()->getFirstName(),
                'prenom' => $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                /** Element en rapport avec la methode de paiement */
                'mode_paiement' => $payroll->getPersonal()->getModePaiement() ?? '',
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                /** Element lieu au cumul des salaire */
                'salaire_brut' => (double)$payroll->getBrutAmount(),
                'charge_salarial' => (double)$payroll->getTotalRetenueSalarie(),
                'charge_patronal' => (double)$payroll->getTotalRetenuePatronal(),
                'amount_avantage' => (double)$payroll->getAventageNonImposable(),
                'net_imposable' => (double)$payroll->getImposableAmount(),
                'heure_travailler' => Status::TAUX_HEURE,
                'nb_heure_supp' => (double)$nbHeure,
                'net_payes' => (double)$payroll->getNetPayer(),
                /** Element en rapport avec le salaire du salarié */
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
                'prime_anciennete' => (double)$payroll->getAncienneteAmount(),
                'total_brut' => (double)$payroll->getImposableAmount(),
                'taux_its' => '0 à 32 %',
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
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
                'assurance_salariale' => (double)$payroll->getSalarySante(),
                'assurance_patronales' => (double)$payroll->getEmployeurSante(),
                'prime_transport' => (double)$payroll->getSalaryTransport(),
                'amount_prime_panier' => (double)$payroll->getAmountPrimePanier(),
                'amount_prime_salissure' => (double)$payroll->getAmountPrimeSalissure(),
                'amount_prime_tt' => (double)$payroll->getAmountPrimeTenueTrav(),
                'amount_prime_outi' => (double)$payroll->getAmountPrimeOutillage(),
                'amount_prime_rendement' => (double)$payroll->getAmountPrimeRendement(),
                'debut_exercise' => $payroll->getCampagne()->getDateDebut() ? date_format($payroll->getCampagne()->getDateDebut(), 'd/m/Y') : '',
                'fin_exercise' => $payroll->getCampagne()->getDateFin() ? date_format($payroll->getCampagne()->getDateFin(), 'd/m/Y') : '',
                'retenue_net' => $payroll->getRetenueNet(),
                'retenue_brut' => $payroll->getRetenueBrut(),
                'remboursement_net' => $payroll->getRemboursNet(),
                'remboursement_brut' => $payroll->getRemboursBrut(),
                'pret_mensuel' => $payroll->getAmountMensualityPret(),
                'acompte_mensuel' => $payroll->getAmountMensuelAcompt()
            ];
        }

        return $this->render('paiement/bulletins.html.twig', [
            'payrolls' => $dataPayroll,
            'caisse' => Status::CAISSE,
            'virement' => Status::VIREMENT
        ]);

    }

    #[Route('/bulletin/all_print', name: 'print_all_bulletin', methods: ['GET'])]
    public function findAllBulletin(): Response
    {
        $payrolls = $this->payrollRepository->findPayrollByCampaign(true);
        $payBookData = [];
        foreach ($payrolls as $payroll) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $periode = $payroll->getCampagne()->getDateDebut();
            $date = $formatter->format($periode);
            $personal = $payroll->getPersonal();
            $accountNumber = null;
            $nameBanque = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
                $nameBanque = $value->getName();
            }

            $month = $periode->format('m');
            $year = $periode->format('Y');
            $nbHeureSupp = $this->heureSupRepository->getNbHeursSupp($personal, $month, $year);
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

            $personalConges = $payroll->getPersonal();
            $conges = $this->congeRepository->getLastCongeByID($personalConges->getId(), false);
            $dernierRetour = $conges?->getDateDernierRetour();

            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();

            $payBookData[] = [
                /** Information de congés */
                'date_dernier_conges' => $dernierRetour,
                'nombre_jour_travailler' => $payroll->getDayOfPresence(),
                /** Element en rapport avec le personal */
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'categorie' => $payroll->getCategories(),
                'Salaire_categoriel' => $payroll->getPersonal()->getCategorie()->getAmount(),
                'nombre_part' => $payroll->getNumberPart(),
                'date_embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'numero_cnps' => $payroll->getNumCnps(),
                'periode_paie' => $date,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'nom' => $payroll->getPersonal()->getFirstName(),
                'prenom' => $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                /** Element en rapport avec la methode de paiement */
                'mode_paiement' => $payroll->getPersonal()->getModePaiement() ?? '',
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                /** Element lieu au cumul des salaire */
                'salaire_brut' => (double)$payroll->getBrutAmount(),
                'charge_salarial' => (double)$payroll->getTotalRetenueSalarie(),
                'charge_patronal' => (double)$payroll->getTotalRetenuePatronal(),
                'amount_avantage' => (double)$payroll->getAventageNonImposable(),
                'net_imposable' => (double)$payroll->getImposableAmount(),
                'heure_travailler' => Status::TAUX_HEURE,
                'nb_heure_supp' => (double)$nbHeure,
                'net_payes' => (double)$payroll->getNetPayer(),
                /** Element en rapport avec le salaire du salarié */
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
                'prime_anciennete' => (double)$payroll->getAncienneteAmount(),
                'total_brut' => (double)$payroll->getImposableAmount(),
                'taux_its' => '0 à 32 %',
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
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
                'assurance_salariale' => (double)$payroll->getSalarySante(),
                'assurance_patronales' => (double)$payroll->getEmployeurSante(),
                'prime_transport' => (double)$payroll->getSalaryTransport(),
                'amount_prime_panier' => (double)$payroll->getAmountPrimePanier(),
                'amount_prime_salissure' => (double)$payroll->getAmountPrimeSalissure(),
                'amount_prime_tt' => (double)$payroll->getAmountPrimeTenueTrav(),
                'amount_prime_outi' => (double)$payroll->getAmountPrimeOutillage(),
                'amount_prime_rendement' => (double)$payroll->getAmountPrimeRendement(),
                'debut_exercise' => $payroll->getCampagne()->getDateDebut() ? date_format($payroll->getCampagne()->getDateDebut(), 'd/m/Y') : '',
                'fin_exercise' => $payroll->getCampagne()->getDateFin() ? date_format($payroll->getCampagne()->getDateFin(), 'd/m/Y') : '',
                'retenue_net' => $payroll->getRetenueNet(),
                'retenue_brut' => $payroll->getRetenueBrut(),
                'remboursement_net' => $payroll->getRemboursNet(),
                'remboursement_brut' => $payroll->getRemboursBrut(),
                'pret_mensuel' => $payroll->getAmountMensualityPret(),
                'acompte_mensuel' => $payroll->getAmountMensuelAcompt()
            ];

        }
        return $this->render('paiement/last.bulletin.html.twig', [
            'payroll_data' => $payBookData,
            'caisse' => Status::CAISSE,
            'virement' => Status::VIREMENT
        ]);
    }

    /** Bulletin de paie pour les campagnes de paie exceptionnelles */
    #[Route('/bulletin/exeptionnel/{uuid}', name: 'bulletin', methods: ['GET'])]
    public function editBulletin(Personal $personal): Response
    {
        $payrolls = $this->payrollRepository->findBulletinByCampaign(true, $personal);
        $reason = null;
        $departurePayroll = null;
        foreach ($payrolls as $payroll) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $periode = $payroll->getCampagne()->getDateDebut();
            $date = $formatter->format($periode);
            $reason = $payroll->getPersonal()->getDepartures()->getReason();
            $totalImposableCnps = $payroll->getTotalIndemniteImposable() ?? 0;
            if ($totalImposableCnps > 1647314) {
                $totalImposableCnps = 1647314;
            }
            $accountNumber = null;
            $nameBanque = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
                $nameBanque = $value->getName();
            }
            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            //$tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();
            $departurePayroll = [
                'indemnite_preavis' => $payroll->getPreavisAmount() ?? 0,
                'indemnite_licenciement_imp' => $payroll->getLicemciementImposable() ?? 0,
                'gratification' => $payroll->getGratificationD() ?? 0,
                'allocation_conges' => $payroll->getAllocationCongeD() ?? 0,
                'total_brut' => $payroll->getTotalIndemniteImposable() ?? 0,
                'taux_its' => '0 à 32 %',
                'amount_its' => $payroll->getSalaryIts() ?? 0,
                'taux_retenu_cnps' => $tauxCnpsSalarial,
                'total_brut_cnps' => $totalImposableCnps,
                'retenu_cnps' => $payroll->getSalaryCnps() ?? 0,
                'taux_retenue_general_cnps' => $tauxCrEmployeur,
                'retenue_general_cnps' => $payroll->getEmployeurCr() ?? 0,
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
                'taux_prest_familliale' => $tauxPfEmployeur,
                'prestation_familliale' => $payroll->getEmployeurPf() ?? 0,
                'taux_accid_travail' => $tauxAtEmployeur,
                'accident_travail' => $payroll->getEmployeurAt() ?? 0,
                'taux_apprentissage' => $tauxTaEmployeur,
                'amount_taux_apprentissage' => $payroll->getAmountTA() ?? 0,
                'taux_fpc' => $tauxFPCEmployeur,
                'amount_fpc' => $payroll->getAmountFPC() ?? 0,
                'taux_fpc_annuel' => $tauxFPCAnnuelEmployeur,
                'amount_fpc_annuel' => $payroll->getAmountAnnuelFPC() ?? 0,
                'total_cotisation_salary' => $payroll->getFixcalAmount() ?? 0,
                'total_cotisation_employeur' => $payroll->getFixcalAmountEmployeur() ?? 0,
                'indemnite_licenciement_no_imp' => $payroll->getLicenciementNoImpo() ?? 0,
                'avantage_en_nature' => 0,
                'heure_travailler' => Status::TAUX_HEURE,
                'heure_supplementaire' => 0,
                'net_payer' => $payroll->getNetPayer() ?? 0,
                'mode_paiement' => $payroll->getPersonal()->getModePaiement() ?? '',
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'categorie' => $payroll->getCategories(),
                'Salaire_categoriel' => $payroll->getPersonal()->getCategorie()->getAmount(),
                'nombre_part' => $payroll->getNumberPart(),
                'date_embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'numero_cnps' => $payroll->getNumCnps(),
                'periode_paie' => $date,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'nom_prenoms' => $payroll->getPersonal()->getFirstName() . ' ' . $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                'debut_exercise' => $payroll->getCampagne()->getDateDebut() ? date_format($payroll->getCampagne()->getDateDebut(), 'd/m/Y') : '',
                'fin_exercise' => $payroll->getCampagne()->getDateFin() ? date_format($payroll->getCampagne()->getDateFin(), 'd/m/Y') : '',

            ];
        }
        return $this->render('paiement/bulletin.html.twig', [
            'payroll_departure' => $departurePayroll,
            'retraite' => Status::RETRAITE,
            'deces' => Status::DECES,
            'reason' => $reason
        ]);
    }

    #[Route('/bulletin/{uuid}', name: 'bulletin_incatif', methods: ['GET'])]
    public function bulletinByCampagneInactif(Personal $personal): Response
    {

        $payrolls = $this->payrollRepository->findBulletinByCampaign(false, $personal);
        $dataPayroll = null;
        foreach ($payrolls as $payroll) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $periode = $payroll->getCampagne()->getDateDebut();
            $date = $formatter->format($periode);
            $accountNumber = null;
            $nameBanque = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
                $nameBanque = $value->getName();
            }

            $month = $periode->format('m');
            $year = $periode->format('Y');
            $nbHeureSupp = $this->heureSupRepository->getNbHeursSupp($personal, $month, $year);
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

            $personalConges = $payroll->getPersonal();
            $conges = $this->congeRepository->getLastCongeByID($personalConges->getId(), false);
            $dernierRetour = $conges?->getDateDernierRetour();

            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();

            $dataPayroll = [
                /** Information de congés */
                'date_dernier_conges' => $dernierRetour,
                'nombre_jour_travailler' => (int)$payroll->getDayOfPresence(),
                /** Element en rapport avec le personal */
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'categorie' => $payroll->getCategories(),
                'Salaire_categoriel' => $payroll->getPersonal()->getCategorie()->getAmount(),
                'nombre_part' => $payroll->getNumberPart(),
                'date_embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'numero_cnps' => $payroll->getNumCnps(),
                'periode_paie' => $date,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'nom' => $payroll->getPersonal()->getFirstName(),
                'prenom' => $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                /** Element en rapport avec la methode de paiement */
                'mode_paiement' => $payroll->getPersonal()->getModePaiement() ?? '',
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                /** Element lieu au cumul des salaire */
                'salaire_brut' => (double)$payroll->getBrutAmount(),
                'charge_salarial' => (double)$payroll->getTotalRetenueSalarie(),
                'charge_patronal' => (double)$payroll->getTotalRetenuePatronal(),
                'amount_avantage' => (double)$payroll->getAventageNonImposable(),
                'net_imposable' => (double)$payroll->getImposableAmount(),
                'heure_travailler' => Status::TAUX_HEURE,
                'nb_heure_supp' => (double)$nbHeure,
                'net_payes' => (double)$payroll->getNetPayer(),
                /** Element en rapport avec le salaire du salarié */
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
                'prime_anciennete' => (double)$payroll->getAncienneteAmount(),
                'total_brut' => (double)$payroll->getImposableAmount(),
                'taux_its' => '0 à 32 %',
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
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
                'assurance_salariale' => (double)$payroll->getSalarySante(),
                'assurance_patronales' => (double)$payroll->getEmployeurSante(),
                'prime_transport' => (double)$payroll->getSalaryTransport(),
                'amount_prime_panier' => (double)$payroll->getAmountPrimePanier(),
                'amount_prime_salissure' => (double)$payroll->getAmountPrimeSalissure(),
                'amount_prime_tt' => (double)$payroll->getAmountPrimeTenueTrav(),
                'amount_prime_outi' => (double)$payroll->getAmountPrimeOutillage(),
                'amount_prime_rendement' => (double)$payroll->getAmountPrimeRendement(),
                'debut_exercise' => $payroll->getCampagne()->getDateDebut() ? date_format($payroll->getCampagne()->getDateDebut(), 'd/m/Y') : '',
                'fin_exercise' => $payroll->getCampagne()->getDateFin() ? date_format($payroll->getCampagne()->getDateFin(), 'd/m/Y') : '',
                'retenue_net' => $payroll->getRetenueNet(),
                'retenue_brut' => $payroll->getRetenueBrut(),
                'remboursement_net' => $payroll->getRemboursNet(),
                'remboursement_brut' => $payroll->getRemboursBrut()
            ];
        }

        return $this->render('paiement/bulletins.html.twig', [
            'payrolls' => $dataPayroll,
            'caisse' => Status::CAISSE,
            'virement' => Status::VIREMENT
        ]);

    }
    #[Route('/validated', name: 'validated', methods: ['POST'])]
    public function ValidatedCampagne(): RedirectResponse
    {
        $campagne = $this->campagneRepository->findCampagnActiveAndPending();

        if($campagne){
            $campagne
            ->setStatus(Status::VALIDATED);
            $this->manager->persist($campagne);
            $this->manager->flush();

            $this->addFlash('success', 'Campagne Validée avec succès');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', "Erreur lors de la validation de la Campagne!");
        return $this->redirectToRoute('campagne_livre');
    }
    #[Route('/canceled', name: 'canceled', methods: ['POST'])]
    public function canceledCampagne(): RedirectResponse
    {
        $campagne = $this->campagneRepository->findCampagnActiveAndPending();

        if($campagne){
            $campagne
            ->setActive(false)
            ->setStatus(Status::CANCELED);
            $this->manager->persist($campagne);
            $this->manager->flush();

            $this->addFlash('success', 'Campagne annulée avec succès');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', "Erreur lors de l'annualation de la Campagne!");
        return $this->redirectToRoute('campagne_livre');
    }
    
    #[Route('/bulletin/print/etat/salaire', name: 'print_bulletin_etat_salaire', methods: ['POST'])]
    public function printBulletinEtatSalaire(Request $request): Response
    {
        $payrolls = [];
        if ($request->request->has('printEtatSalaireInput') && $request->isMethod('POST')) {
            
            $dateRequest = $request->request->get('printEtatSalaireInput');
            if ($dateRequest) {
                $dateRequestObj = DateTime::createFromFormat('Y-m', $dateRequest);
                $dateDebut = $dateRequestObj->format('Y-m-01');
                $dateFin = $dateRequestObj->format('Y-m-t');
                $startAt = new DateTime($dateDebut);
                $endAt = new DateTime($dateFin);
            }
            $personalID = (int)$request->get('personalsIdInput');
            $payrolls = $this->payrollRepository->findByPeriode($startAt, $endAt, $personalID);
        }
        $payBookData = [];
        foreach ($payrolls as $payroll) {
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
            $periode = $payroll->getCampagne()->getDateDebut();
            $date = $formatter->format($periode);
            $personal = $payroll->getPersonal();
            $accountNumber = null;
            $nameBanque = null;
            $accountBanque = $payroll->getPersonal()->getAccountBanks();
            foreach ($accountBanque as $value) {
                $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
                $nameBanque = $value->getName();
            }

            $month = $periode->format('m');
            $year = $periode->format('Y');
            $nbHeureSupp = $this->heureSupRepository->getNbHeursSupp($personal, $month, $year);
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

            $personalConges = $payroll->getPersonal();
            $conges = $this->congeRepository->getLastCongeByID($personalConges->getId(), false);
            $dernierRetour = $conges?->getDateDernierRetour();

            $tauxCnpsSalarial = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS'])->getValue();
            $tauxCrEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR'])->getValue();
            $tauxPfEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF'])->getValue();
            $tauxAtEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT'])->getValue();
            $tauxIsEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'IS'])->getValue();
            $tauxTaEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA'])->getValue();
            $tauxFPCEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC'])->getValue();
            $tauxFPCAnnuelEmployeur = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER'])->getValue();

            $payBookData[] = [
                /** Information de congés */
                'date_dernier_conges' => $dernierRetour,
                'nombre_jour_travailler' => $payroll->getDayOfPresence(),
                /** Element en rapport avec le personal */
                'matricule' => $payroll->getMatricule(),
                'service' => $payroll->getService(),
                'categorie' => $payroll->getCategories(),
                'Salaire_categoriel' => $payroll->getPersonal()->getCategorie()->getAmount(),
                'nombre_part' => $payroll->getNumberPart(),
                'date_embauche' => date_format($payroll->getDateEmbauche(), 'd/m/Y'),
                'numero_cnps' => $payroll->getNumCnps(),
                'periode_paie' => $date,
                'date_edition' => date_format($payroll->getCampagne()->getStartedAt(), 'd/m/Y'),
                'nom' => $payroll->getPersonal()->getFirstName(),
                'prenom' => $payroll->getPersonal()->getLastName(),
                'departement' => $payroll->getDepartement(),
                /** Element en rapport avec la methode de paiement */
                'mode_paiement' => $payroll->getPersonal()->getModePaiement() ?? '',
                'account_number' => $accountNumber,
                'banque_name' => $nameBanque,
                /** Element lieu au cumul des salaire */
                'salaire_brut' => (double)$payroll->getBrutAmount(),
                'charge_salarial' => (double)$payroll->getTotalRetenueSalarie(),
                'charge_patronal' => (double)$payroll->getTotalRetenuePatronal(),
                'amount_avantage' => (double)$payroll->getAventageNonImposable(),
                'net_imposable' => (double)$payroll->getImposableAmount(),
                'heure_travailler' => Status::TAUX_HEURE,
                'nb_heure_supp' => (double)$nbHeure,
                'net_payes' => (double)$payroll->getNetPayer(),
                /** Element en rapport avec le salaire du salarié */
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
                'prime_anciennete' => (double)$payroll->getAncienneteAmount(),
                'total_brut' => (double)$payroll->getImposableAmount(),
                'taux_its' => '0 à 32 %',
                'smig' => (double)$payroll->getPersonal()->getSalary()->getSmig(),
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
                'assurance_salariale' => (double)$payroll->getSalarySante(),
                'assurance_patronales' => (double)$payroll->getEmployeurSante(),
                'prime_transport' => (double)$payroll->getSalaryTransport(),
                'amount_prime_panier' => (double)$payroll->getAmountPrimePanier(),
                'amount_prime_salissure' => (double)$payroll->getAmountPrimeSalissure(),
                'amount_prime_tt' => (double)$payroll->getAmountPrimeTenueTrav(),
                'amount_prime_outi' => (double)$payroll->getAmountPrimeOutillage(),
                'amount_prime_rendement' => (double)$payroll->getAmountPrimeRendement(),
                'debut_exercise' => $payroll->getCampagne()->getDateDebut() ? date_format($payroll->getCampagne()->getDateDebut(), 'd/m/Y') : '',
                'fin_exercise' => $payroll->getCampagne()->getDateFin() ? date_format($payroll->getCampagne()->getDateFin(), 'd/m/Y') : '',
                'retenue_net' => $payroll->getRetenueNet(),
                'retenue_brut' => $payroll->getRetenueBrut(),
                'remboursement_net' => $payroll->getRemboursNet(),
                'remboursement_brut' => $payroll->getRemboursBrut(),
                'pret_mensuel' => $payroll->getAmountMensualityPret(),
                'acompte_mensuel' => $payroll->getAmountMensuelAcompt()
            ];

        }
        return $this->render('paiement/last.bulletin.html.twig', [
            'payroll_data' => $payBookData,
            'caisse' => Status::CAISSE,
            'virement' => Status::VIREMENT
        ]);
    }
    
}