<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Form\DossierPersonal\CongeType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Paiement\CampagneRepository;
use App\Service\CongeService;
use App\Utils\Status;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/conge', name: 'conge_')]
class CongeController extends AbstractController
{

    private PersonalRepository $personalRepository;
    private CongeRepository $congeRepository;
    private CongeService $congeService;
    private CampagneRepository $campagneRepository;

    public function __construct(
        PersonalRepository $personalRepository,
        CongeRepository    $congeRepository,
        CongeService       $congeService,
        CampagneRepository $campagneRepository
    )
    {
        $this->personalRepository = $personalRepository;
        $this->congeRepository = $congeRepository;
        $this->congeService = $congeService;
        $this->campagneRepository = $campagneRepository;
    }


    #[Route('/api/conge_book/', name: 'api_book', methods: ['GET'])]
    public function getCongesSalaried(): JsonResponse
    {
        $personals = $this->personalRepository->findAllPersonal();
        $conges = $this->congeRepository->findConge(Status::CONGE_GLOBAL);
        $today = Carbon::now();
        $genre = null;
        $chargPeapleOfPersonal = null;
        $anciennete = null;
        $congeSalaried = [];
        foreach ($personals as $personal) {
            $genre = $personal->getGenre();
            $chargPeapleOfPersonal = $personal->getChargePeople();
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
            $anciennete = ceil($today->diff($dateEmbauche)->y);
        }
        $suppConger = $this->congeService->suppConger($genre, $chargPeapleOfPersonal, $today);
        foreach ($conges as $conge => $item) {
            $dateDebut = $item['depart'];
            $dateRetour = $item['retour'];
            if ($genre === Status::FEMININ) {
                $dureeJourCongeAnnuel = ceil((($dateDebut->diff($dateRetour)->days) + $suppConger));
            } else {
                $dureeJourCongeAnnuel = ceil(($dateDebut->diff($dateRetour)->days));
            }
            $nbJourCongesPaye = $dureeJourCongeAnnuel + $this->congeService->echelonConge($anciennete);
            $congeSalaried[] = [
                'index' => ++$conge,
                'full_name' => $item['nom'] . ' ' . $item['prenoms'],
                'date_depart' => date_format($dateDebut, 'd/m/Y'),
                'date_retour' => date_format($dateRetour, 'd/m/Y'),
                'conges_annuel_jour' => $nbJourCongesPaye,
                'dernier_conge' => date_format($item['dernier_retour'], 'd/m/Y'),
                'salaire_moyen' => $item['salaire_moyen'],
                'allocation_annuel' => $item['allocation_conge'],
                'en_conge_?' => $item['en_conge'] === true ? 'OUI' : 'NOM',
                'modifier' => $this->generateUrl('conge_edit', ['uuid' => $item['uuid']])
            ];
        }
        return new JsonResponse($congeSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CongeRepository $congeRepository): Response
    {
        $today = Carbon::now();
        $years = $today->year;
        $month = $today->month;
        return $this->render('dossier_personal/conge/index.html.twig', [
            'conges' => $congeRepository->findAll(),
            'mois' => $month,
            'annee' => $years
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CongeService $congeService): Response
    {
        $conge = new Conge();
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);
        $today = Carbon::today();
        if ($form->isSubmitted() && $form->isValid()) {
            $dateDernierRetour = $form->get('dateRetour')->getData();
            $date = new Carbon($dateDernierRetour);
            $personal = $form->get('personal')->getData();
            $active = $this->congeRepository->active($personal);
            $checkPersonalCampagne = $this->campagneRepository->checkPersonalInCampagne($personal);
            if (!$checkPersonalCampagne) {
                $this->addFlash('error', 'Monsieur ou Madame ' . $personal->getFirstName() . ' n\'est pas éligible pour obtenir un congé.');
                return $this->redirectToRoute('conge_index');
            }
            if ($active && $today->diff($date)->days <= 30) {
                $this->addFlash('error', 'Monsieur ou Madame ' . $personal->getFirstName() . ' n\'est pas encore de retour du congé précédent.');
                return $this->redirectToRoute('conge_index');
            }

            $congeService->calculate($conge);
            $conge
                ->setDateDernierRetour($dateDernierRetour)
                ->setTypeConge(Status::CONGE_GLOBAL)
                ->setIsConge(true);
            $entityManager->persist($conge);
            $entityManager->flush();

            flash()->addSuccess('Congé planifié avec succès.');
            return $this->redirectToRoute('conge_index');
        }

        return $this->render('dossier_personal/conge/new.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addSuccess('Congé planifié modifier avec succès.');
            return $this->redirectToRoute('conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

}
