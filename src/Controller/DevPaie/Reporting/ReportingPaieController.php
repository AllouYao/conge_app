<?php

namespace App\Controller\DevPaie\Reporting;

use Carbon\Carbon;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reporting_paie', name: 'reporting_paie_')]
class ReportingPaieController extends AbstractController
{
    #[Route('/remboursement_salaires', name: 'remboursement_salaires', methods: ['GET', 'POST'])]
    public function viewRemboursementSalaire(): Response
    {
        return $this->render('dev_paie/reportings/operations/remboursement/remboursement.html.twig');
    }

    #[Route('/validated_remboursement_salaire', name: 'validated_remboursement', methods: ['GET', 'POST'])]
    public function viewValidateRemboursement(): Response
    {
        return $this->render('dev_paie/reportings/operations/remboursement/remboursement.validated.html.twig');
    }

    #[Route('/retenue_salaires', name: 'retenue_salaires', methods: ['GET', 'POST'])]
    public function viewRetenueSalaire(): Response
    {
        return $this->render('dev_paie/reportings/operations/retenue_salaire/retenue_salaries.html.twig');
    }

    #[Route('/validated_retenue_salaires', 'validated_retenue_salaires', methods: ['GET', 'POST'])]
    public function viewValidateRetenueSalaire(): Response
    {
        return $this->render('dev_paie/reportings/operations/retenue_salaire/retenue.validated.html.twig');
    }

    #[Route('/regularisation_salaire', name: 'regularisation_salaire', methods: ['POST', 'GET'])]
    public function viewEtatMensuelRegularisation(): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM Y');
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dev_paie/reportings/regularisations/regul_mensuel.html.twig', [
            'date' => $date,
        ]);
    }
}