<?php

namespace App\Controller;

use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\JobRepository;
use App\Repository\Settings\ServiceRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
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

    #[Route(path: ['/home', '/'], name: 'app_home')]
    public function index(): Response
    {
        //$messageBus->dispatch(new UpdateOlderPersonal());
        $today = Carbon::today();
        $congerEnCours = $this->congeRepository->activeForAll();
        foreach ($congerEnCours as $enCour) {
            $dateFin = $enCour->getDateRetour();
            if ($dateFin <= $today) {
                $enCour->setIsConge(false);
                flash()->addInfo('le salarié ' . $enCour->getPersonal()->getFirstName() . ' ' . $enCour->getPersonal()->getLastName() . ' est de retour de congé.');
            }
            $this->manager->persist($enCour);
            $this->manager->flush();

        }
       /* $directory = $this->getParameter('kernel.project_dir');
        $filePath = $directory. DIRECTORY_SEPARATOR. 'public'.DIRECTORY_SEPARATOR . 'personal.csv';
        $reader = new Csv();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet()->toArray();
        foreach ($worksheet as $key => $row) {
            if ($key > 0) {
                $pId = $row[0];
                $workplaceName = $row[1];
                $jobName = $row[2];
                $job = $jobRepository->findOneBy(['name' => trim($jobName)]);
                $workPlace = $serviceRepository->findOneBy(['name' => trim($workplaceName)]);
                $p = $personalRepository->find($pId);
                if ($p) {
                    $p->setworkPlace($workPlace)->setJob($job);
                    $this->manager->persist($p);
                }
            }
        }
        $this->manager->flush();*/

        return $this->render('home/index.html.twig');
    }
}
