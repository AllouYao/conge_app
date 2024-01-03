<?php

namespace App\EventListener;

use App\Repository\Paiement\CampagneRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Flasher\Prime\FlasherInterface;
use Symfony\Component\Routing\RouterInterface;

class CampagneEventListener
{
    private $flasher;
    private $router;



    public function __construct(FlasherInterface $flasher,RouterInterface $router)
    {
        $this->flasher = $flasher;
        $this->router = $router;

    }

    public function onKernelRequest(RequestEvent $event,CampagneRepository $campagneRepository)
    {
        dd($event);
        $routes = ['personal_heure_sup_new'];
        $currentRoute = $event->getRequest()->attributes->get('_route');
        $campagneActives = $campagneRepository->getCampagneActives();


        if (!$campagneActives) {

            foreach ($routes as $route) {
                
                if ($currentRoute == $route) {

                    //$this->flasher->addFlash('error', "Aucune modifcation n'est possible ");
                   // return $this->redirectToRoute('app_home');
                }
            }
            
        }

        
    }

}