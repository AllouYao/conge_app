<?php

namespace App\Controller\Test;

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
}
