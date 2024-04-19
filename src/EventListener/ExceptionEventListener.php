<?php

namespace App\EventListener;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ExceptionEventListener
{
    private $router;



    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }
    

    public function onKernelException(RequestEvent $event)
    {
        // Check any Exeption on the program
        dd("Test");
        $url = $this->router->generate('exception');


        $response = new RedirectResponse($url);

        $event->setResponse($response);

    }

   
}