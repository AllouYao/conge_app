<?php

namespace App\EventListener;

use Symfony\Component\Routing\RouterInterface;
use App\Repository\Paiement\CampagneRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CampagneEventListener
{
    private $router;
    private $campagneRepository;



    public function __construct(
        RouterInterface $router,
        CampagneRepository $campagneRepository,
    )
    {
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
                
                $url = $this->router->generate('campagne_alert_progess');

                $response = new RedirectResponse($url);

                $event->setResponse($response);

            }
        }
    }

    private function checkRoute($currentRoute): bool
    {

        $allRoutes = $this->router->getRouteCollection()->all();

        $routes = array_filter($allRoutes, function ($route) {
            $path = $route->getPath();
            return substr($path, -4) === 'edit' || substr($path, -3) === 'new';
        });

        foreach ($routes as $route) {

            if ($route->getPath() == $currentRoute) {

                return true;
            }
        }

        return false;
    }
}