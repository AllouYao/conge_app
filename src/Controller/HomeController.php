<?php

namespace App\Controller;

use Carbon\Carbon;
use App\Repository\CongeRepository;
use App\Repository\PersonalRepository;
use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    public function __construct(
        private CongeRepository $congeRepository,
        private PersonalRepository $personalRepository,
        private UserRepository $userRepository,
        private ServiceRepository $serviceRepository,
        private CategoryRepository $categoryRepository
    ) {
    }

    #[Route(path: ['/home', '/'], name: 'app_home')]
    public function index(): Response
    {
        // Statistiques générales
        $totalConges = $this->congeRepository->count([]);
        $congesEnAttente = $this->congeRepository->count(['status' => 'En attente']);
        $congesValides = $this->congeRepository->count(['status' => 'Validé']);
        $congesRefuses = $this->congeRepository->count(['status' => 'Refusé']);
        $congesEnCours = $this->congeRepository->count(['isConge' => true]);
        
        $totalPersonnels = $this->personalRepository->count([]);
        $totalUsers = $this->userRepository->count([]);
        $totalServices = $this->serviceRepository->count([]);
        $totalCategories = $this->categoryRepository->count([]);

        // Congés par statut pour le graphique
        $congesParStatut = [
            'En attente' => $congesEnAttente,
            'Validé' => $congesValides,
            'Refusé' => $congesRefuses,
        ];

        // Congés des 6 derniers mois
        $congesParMois = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth()->toDateTime();
            $endOfMonth = $date->copy()->endOfMonth()->toDateTime();
            
            $count = $this->congeRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.dateDepart >= :start')
                ->andWhere('c.dateDepart <= :end')
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();
            
            // Format français du mois
            $moisFr = [
                'Jan' => 'Jan', 'Feb' => 'Fév', 'Mar' => 'Mar', 'Apr' => 'Avr',
                'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Aoû',
                'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Déc'
            ];
            $moisEn = $date->format('M');
            $mois = isset($moisFr[$moisEn]) ? $moisFr[$moisEn] : $moisEn;
            
            $congesParMois[] = [
                'mois' => $mois . ' ' . $date->format('Y'),
                'count' => (int)$count
            ];
        }

        // Derniers congés (5 plus récents)
        $derniersConges = $this->congeRepository->createQueryBuilder('c')
            ->join('c.personal', 'p')
            ->addSelect('p')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Congés en cours
        $congesActifs = $this->congeRepository->createQueryBuilder('c')
            ->join('c.personal', 'p')
            ->addSelect('p')
            ->where('c.isConge = :isConge')
            ->setParameter('isConge', true)
            ->orderBy('c.dateRetour', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Congés à venir (prochains 30 jours)
        $today = Carbon::now()->toDateTime();
        $in30Days = Carbon::now()->addDays(30)->toDateTime();
        
        $congesAvenir = $this->congeRepository->createQueryBuilder('c')
            ->join('c.personal', 'p')
            ->addSelect('p')
            ->where('c.dateDepart >= :today')
            ->andWhere('c.dateDepart <= :in30Days')
            ->andWhere('c.status = :status')
            ->setParameter('today', $today)
            ->setParameter('in30Days', $in30Days)
            ->setParameter('status', 'Validé')
            ->orderBy('c.dateDepart', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'totalConges' => $totalConges,
            'congesEnAttente' => $congesEnAttente,
            'congesValides' => $congesValides,
            'congesRefuses' => $congesRefuses,
            'congesEnCours' => $congesEnCours,
            'totalPersonnels' => $totalPersonnels,
            'totalUsers' => $totalUsers,
            'totalServices' => $totalServices,
            'totalCategories' => $totalCategories,
            'congesParStatut' => $congesParStatut,
            'congesParMois' => $congesParMois,
            'derniersConges' => $derniersConges,
            'congesActifs' => $congesActifs,
            'congesAvenir' => $congesAvenir,
        ]);
    }
}
