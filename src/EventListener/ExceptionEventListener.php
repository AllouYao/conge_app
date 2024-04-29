<?php

namespace App\EventListener;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionEventListener
{
    private $router;



    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        // Check any Exeption on the program
        if($event->getThrowable()){

            /*
            $url = $this->router->generate('app_exception');

            $response = new RedirectResponse($url);

            $event->setResponse($response);
            */
        }

    }

   
}