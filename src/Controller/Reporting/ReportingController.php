<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\PersonalRepository;
use Carbon\Carbon;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reporting', name: 'reporting_')]
class ReportingController extends AbstractController
{

    #[Route('/etat_salaire', name: 'etat_salaire', methods: ['GET', 'POST'])]
    public function viewEtatSalaireGlobal(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/etat_salaire/etat.salaire.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration-dgi', name: 'declaration_dgi', methods: ['GET', 'POST'])]
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

    #[Route('/declaration-dgi-current-month', name: 'declaration_dgi_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelDgi(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_dgi/declaration_mensuelle.dgi.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_cnps-current-month', name: 'declaration_cnps_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelCnps(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_fdfp/declaration_mensuelle.cnps.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/declaration_fdfp-current-month', name: 'declaration_fdfp_current_month', methods: ['GET', 'POST'])]
    public function viewDeclarationMensuelFdfp(PersonalRepository $personalRepository): Response
    {
        return $this->render('reporting/declaration_cnps/declaration_mensuelle.fdfp.html.twig', [
            'personals' => $personalRepository->findPersonalWithContract()
        ]);
    }

    #[Route('/prime_indemnite', name: 'prime_indemnite', methods: ['GET', 'POST'])]
    public function viewPrimeIndemnitesMensuel(): Response
    {
        $today = Carbon::today();
        $month = $today->month;
        $years = $today->year;
        return $this->render('reporting/prime_indemnite/prime_indemnite.html.twig', [
            'mois' => $month,
            'annee' => $years
        ]);
    }


    #[Route('/element_variable', name: 'element_variable', methods: ['GET', 'POST'])]
    public function viewElementVariable(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('reporting/element_variable/element_variable.html.twig', [
            'date' => $date
        ]);
    }

    #[Route('/etat_versement', name: 'etat_versement', methods: ['GET', 'POST'])]
    public function viewEtatVersement(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('reporting/etat_versement/versement.html.twig', [
            'date' => $date
        ]);
    }

}