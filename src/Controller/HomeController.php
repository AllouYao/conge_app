<?php

namespace App\Controller;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    public function __construct(private EntityManagerInterface $entityManager )
    {
    }

    #[Route(path: ['/home', '/'], name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
