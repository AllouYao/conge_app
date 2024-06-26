<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExceptionController extends AbstractController
{

    public function __construct()
    {
    }

    #[Route('   /exception', name: 'app_exception')]
    public function catchException(): Response
    {
        flash()->addError("Oups! Une erreur est survenue lors du traitement.");
        return $this->render('exception/exception.htlm.twig');
    }
}
