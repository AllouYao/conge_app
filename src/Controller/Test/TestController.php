<?php

namespace App\Controller\Test;

use App\Entity\DossierPersonal\Departure;
use DatePeriod;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('');
    }

    #[NoReturn] public function getPeriode(Departure $departure): void
    {
        // Obtenir la date de depart ou date actuel
        $dateDepart = $departure->getDate();
        // Creer un interval de 1 mois
        $interval = new \DateInterval('P1M');
        // Periode de 12 mois en arrière à partir de la date actuel
        $debutPeriode = $dateDepart->modify('-12 months');
        $finPeriode = new \DateTime();
        $periode = new DatePeriod($debutPeriode, $interval, $finPeriode);
        // tableau de mois de la periode
        $moisPeriode = [];
        foreach ($periode as $date) {
            $moisPeriode[] = $date->format('F');
        }
        dd($dateDepart, $interval, $debutPeriode, $finPeriode, $periode, $moisPeriode);
    }
}
