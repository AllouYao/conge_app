<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Paiement\CampagneRepository;
use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reporting', name: 'reporting_')]
class ReportingController extends AbstractController
{
    public function __construct(
        private readonly CampagneRepository $campagneRepository
    )
    {
    }

    #[Route('/prime_indemnite', name: 'prime_indemnite', methods: ['GET', 'POST'])]
    public function viewPrimeIndemnitesMensuel(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('reporting/prime_indemnite/prime_indemnite.html.twig', [
            'date' => $date,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/element_variable', name: 'element_variable', methods: ['GET', 'POST'])]
    public function viewElementVariable(): Response
    {
        $campagne = $this->campagneRepository->active();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $date = $campagne ? $formatter->format($campagne->getDateDebut()) : ' ';

        return $this->render('reporting/element_variable/element_variable.html.twig', [
            'date' => $date
        ]);
    }

    #[Route('/etat_salaire', name: 'etat_salaire', methods: ['GET', 'POST'])]
    public function viewEtatSalaireGlobal(PersonalRepository $personalRepository): Response
    {
        if ($this->isGranted('ROLE_RH')) {
            $personals = $personalRepository->findPersoRequest();
        } else {
            $personals = $personalRepository->findPersoReqRole();
        }

        $months = array(
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
        );

        return $this->render('reporting/etat_salaire/etat.salaire.html.twig', [
            'personals' => $personals,
            'months' => $months
        ]);
    }

    #[Route('/etat_versement_annuels', name: 'etat_versement_annuel', methods: ['GET', 'POST'])]
    public function viewEtatVersementAnnuel(PersonalRepository $personalRepository): Response
    {
        if ($this->isGranted('ROLE_RH')) {
            $personals = $personalRepository->findPersoRequest();
        } else {
            $personals = $personalRepository->findPersoReqRole();
        }

        $months = array(
            1 => "Janvier",
            2 => "Février",
            3 => "Mars",
            4 => "Avril",
            5 => "Mai",
            6 => "Juin",
            7 => "Juillet",
            8 => "Août",
            9 => "Septembre",
            10 => "Octobre",
            11 => "Novembre",
            12 => "Décembre"
        );

        return $this->render('reporting/etat_versement/virement.annuel.html.twig', [
            'personals' => $personals,
            'months' => $months
        ]);
    }










    #[Route('/declaration_dgi', name: 'declaration_dgi', methods: ['GET', 'POST'])]
    public function viewDeclarationDgi(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_dgi/declaration.dgi.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_cnps', name: 'declaration_cnps', methods: ['GET', 'POST'])]
    public function viewDeclarationCnps(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_fdfp/declaration.cnps.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_fdfp', name: 'declaration_fdfp', methods: ['GET', 'POST'])]
    public function viewDeclarationFdfp(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_cnps/declaration.fdfp.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }


    #[Route('/salariale_etat_mensuel', name: 'salaires', methods: ['GET', 'POST'])]
    public function viewEtatSalaireMensuel(): Response
    {
        $today = Carbon::today();
        $month = $today->month;
        $years = $today->year;
        return $this->render('reporting/etat_salaire/etat.salaire.mensuel.html.twig', [
            'mois' => $month,
            'annee' => $years
        ]);
    }

    #[Route('/declaration_dgi_current_month', name: 'declaration_dgi_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelDgi(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_dgi/declaration_mensuelle.dgi.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_cnps_current_month', name: 'declaration_cnps_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelCnps(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_fdfp/declaration_mensuelle.cnps.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_fdfp_current_month', name: 'declaration_fdfp_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelFdfp(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_cnps/declaration_mensuelle.fdfp.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }


    #[Route('/etat_versement_caisse_annuels', name: 'etat_versement_caisse_annuel', methods: ['GET', 'POST'])]
    public function viewEtatVersementCaisseAnnuel(PersonalRepository $personalRepository): Response
    {
        if ($this->isGranted('ROLE_RH')) {
            $personals = $personalRepository->findAllPersonalOnCampain();
        } else {
            $personals = $personalRepository->findAllPersonalByEmployeRole();
        }
        return $this->render('reporting/etat_versement/caisse.annuel.html.twig', [
            'personals' => $personals
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/etat_versement_caisse_mensuel', name: 'etat_versement_caisse', methods: ['GET', 'POST'])]
    public function viewEtatVersementCaisse(CampagneRepository $campagneRepository): Response
    {
        $campagne = $campagneRepository->active();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $date = $campagne ? $campagne->getDateDebut() : ' ';
        $periode = $formatter->format($date);
        return $this->render('reporting/etat_versement/caisse.html.twig', [
            'periode' => $periode
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/etat_versement_mensuel', name: 'etat_versement', methods: ['GET', 'POST'])]
    public function viewEtatVersement(CampagneRepository $campagneRepository): Response
    {
        $campagne = $campagneRepository->active();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $date = $campagne ? $campagne->getDateDebut() : ' ';
        $periode = $formatter->format($date);
        return $this->render('reporting/etat_versement/versement.html.twig', [
            'periode' => $periode
        ]);
    }
}