<?php

namespace App\Controller\DevPaie\Reporting;

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
}