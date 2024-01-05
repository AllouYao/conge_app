<?php

namespace App\EventListener;

use App\Repository\Paiement\CampagneRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Flasher\Prime\FlasherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;

class CampagneEventListener
{
    private $flasher;
    private $router;
    private $campagneRepository;



    public function __construct(FlasherInterface $flasher,RouterInterface $router, CampagneRepository $campagneRepository)
    {
        $this->flasher = $flasher;
        $this->router = $router;
        $this->campagneRepository = $campagneRepository;


    }

    public function onKernelRequest(RequestEvent $event)
    {
        


        $currentRoute = $event->getRequest()->getPathInfo();


        $campagneActives = $this->campagneRepository->getCampagneActives();


        if ($campagneActives) {

                // checking path endWith new and edit with current path
            if ($this->checkRoute($currentRoute)) {

                $this->flasher->addFlash('error', "vous avez une campagne en cours: Aucune modifictaion possible !");

                $url = $this->router->generate('app_home');
                
                //flash()->addFlash('error', "vous avez une campagne en cours: Aucune modifictaion possible !");
                $response = new RedirectResponse($url);

                $event->setResponse($response);
            }
            
        }


        
    }

    private function checkRoute($currentRoute):bool
    {
        
        $allRoutes = $this->router->getRouteCollection()->all();

        $routes = array_filter($allRoutes, function ($route) {
            $path = $route->getPath();
            return substr($path, -4) === 'edit' || substr($path, -3) === 'new';
        });

        foreach($routes as $route) {

            if ($route->getPath() == $currentRoute) {

                return true;
            }
        }

        return false;
    }


}