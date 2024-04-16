<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Entity\User;
use App\Form\DossierPersonal\CongeType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Service\CongeService;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/conge', name: 'conge_')]
class CongeController extends AbstractController
{

    private CongeRepository $congeRepository;

    public function __construct(
        CongeRepository $congeRepository,
    )
    {
        $this->congeRepository = $congeRepository;
    }


    #[Route('/api/conge_book/', name: 'api_book', methods: ['GET'])]
    public function getCongesSalaried(): JsonResponse
    {
        $conges = $this->congeRepository->findConge();
        $congeSalaried = [];
        foreach ($conges as $conge => $item) {
            $link = $this->generateUrl('conge_edit', ['uuid' => $item['uuid']]);
            $modifier = $item['en_conge'] === true ? $link : null;
            $dateDebut = $item['depart'];
            $dateRetour = $item['retour'];
            $congeSalaried[] = [
                'index' => ++$conge,
                'full_name' => $item['nom'] . ' ' . $item['prenoms'],
                'date_depart' => date_format($dateDebut, 'd/m/Y'),
                'date_retour' => date_format($dateRetour, 'd/m/Y'),
                'conges_annuel_jour' => $item['totalDays'],
                'conges_jour_pris' => $item['days'],
                'dernier_conge' => date_format($item['dernier_retour'], 'd/m/Y'),
                'salaire_moyen' => $item['salaire_moyen'],
                'allocation_annuel' => $item['allocation_conge'],
                'status' => $item['en_conge'] === true ? 'OUI' : 'NON',
                'jour_restant' => $item['remainingVacation'],
                'modifier' => $modifier
            ];
        }
        return new JsonResponse($congeSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CongeRepository $congeRepository): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/conge/index.html.twig', [
            'conges' => $congeRepository->findAll(),
            'date' => $date
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager,
        CongeService           $congeService,
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $currentUser
         */

        $currentUser = $this->getUser();
        $conge = new Conge();
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $personal = $conge->getPersonal();
            $lastConge = $congeRepository->getLastCongeByID($personal->getId(), false);
            $lastDateReturn = !$lastConge ? $conge->getDateRetour() : $lastConge->getDateDernierRetour();
            /** Verifier si le salarié sélectionner est déjà en congés */
            $congeActive = $congeRepository->getLastCongeByID($personal->getId(), true);
            if ($congeActive) {
                flash()->addInfo('Mr/Mdm' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' 
                est actuellement en congés n\'est donc pas éligible pour une acquisition de congés.');
                return $this->redirectToRoute('conge_index');
            }
            if ($lastConge) {
                $congeService->congesPayerByLast($conge);
                if (!$congeService->success) {
                    flash()->addInfo($congeService->messages);
                    return $this->redirectToRoute('conge_new');
                }
            }
            $congeService->congesPayerByFirst($conge);

            if (!$congeService->success) {

                flash()->addInfo($congeService->messages);
                return $this->redirectToRoute('conge_new');
            }

            $conge
                ->setDateDernierRetour($lastDateReturn)
                ->setIsConge(true)
                ->setUser($currentUser);

            $entityManager->persist($conge);
            $entityManager->flush();

            flash()->addSuccess('Congé planifié avec succès.');
            return $this->redirectToRoute('conge_index');
        }

        return $this->render('dossier_personal/conge/new.html.twig', [
            'conge' => $conge,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @throws Exception
     */
    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Conge                  $conge,
        EntityManagerInterface $entityManager,
        CongeService           $congeService,
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();

        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personal = $conge->getPersonal();
            $lastConge = $congeRepository->getLastCongeByID($personal->getId(), false);
            if ($lastConge)
                $congeService->congesPayerByLast($conge);
            $congeService->congesPayerByFirst($conge);
            $conge->setUser($currentUser);
            $entityManager->persist($conge);
            $entityManager->flush();
            flash()->addSuccess('Congé planifié modifier avec succès.');
            return $this->redirectToRoute('conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $form->createView(),
        ]);
    }

}
