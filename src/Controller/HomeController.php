<?php

namespace App\Controller;

use App\Repository\DossierPersonal\CongeRepository;
use App\Scheduler\UpdateOlderPersonal;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private CongeRepository $congeRepository;
    private EntityManagerInterface $manager;

    public function __construct(CongeRepository $congeRepository, EntityManagerInterface $manager)
    {
        $this->congeRepository = $congeRepository;
        $this->manager = $manager;
    }

    #[Route('/home', name: 'app_home')]
    public function index(MessageBusInterface $messageBus): Response
    {
        //$messageBus->dispatch(new UpdateOlderPersonal());
        $today = Carbon::today();
        $congerEnCours = $this->congeRepository->activeForAll();
        foreach ($congerEnCours as $enCour) {
            $dateFin = $enCour->getDateRetour();
            if ($dateFin <= $today) {
                $enCour->setIsConge(false);
            }
            $this->manager->persist($enCour);
            $this->manager->flush();
            flash()->addInfo('le salarié ' . $enCour->getPersonal()->getFirstName() . ' ' . $enCour->getPersonal()->getLastName() . ' est de retour de congé.');
        }
        
        return $this->render('home/index.html.twig');
    }
}
