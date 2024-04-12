<?php

namespace App\Controller\DevPaie\Reporting;

use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Paiement\CampagneRepository;
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
    public function viewEtatMensuelRegularisation(CampagneRepository $campagneRepository): Response
    {
        $campagne = $campagneRepository->active();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $date = $campagne ? $formatter->format($campagne->getDateDebut()) : ' ';

        return $this->render('dev_paie/reportings/regularisations/regul_mensuel.html.twig', [
            'date' => $date,
        ]);
    }

    #[Route('/regularisation_salaire_periodique', name: 'regularisation_salaire_periodique', methods: ['POST', 'GET'])]
    public function viewEtatPeriodiqueRegularisation(PersonalRepository $personalRepository): Response
    {
        if ($this->isGranted('ROLE_RH')) {
            $personals = $personalRepository->findAllPersonalOnCampain();
        } else {
            $personals = $personalRepository->findAllPersonalByEmployeRole();
        }
        return $this->render('dev_paie/reportings/regularisations/regul_periodique.html.twig', [
            'personals' => $personals
        ]);
    }
}