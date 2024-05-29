<?php

namespace App\Controller;

use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Repository\Paiement\PayrollRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    public function __construct()
    {
        $this->manager = $manager;
    }

    #[Route(path: ['/home', '/'], name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
