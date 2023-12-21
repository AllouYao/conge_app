<?php

namespace App\Controller\Reporting;

use App\Repository\DossierPersonal\PersonalRepository;
use Carbon\Carbon;
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

}