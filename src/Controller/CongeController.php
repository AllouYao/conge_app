<?php

namespace App\Controller;

use DateTime;
use Exception;
use Carbon\Carbon;
use App\Entity\User;
use App\Entity\Conge;
use IntlDateFormatter;
use App\Form\CongeType;
use App\Entity\Personal;
use App\Repository\CongeRepository;
use App\Repository\OldCongeRepository;
use App\Repository\PersonalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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


    #[Route('/personal/{id}/info', name: 'personal_info', methods: ['GET'])]
    public function getPersonalInfo(int $id, PersonalRepository $personalRepository): JsonResponse
    {
        $personal = $personalRepository->find($id);
        
        if (!$personal) {
            return new JsonResponse(['error' => 'Personal not found'], 404);
        }

        $data = [
            'name' => $personal->getFirstName() . ' ' . $personal->getLastName(),
            'hireDate' => $personal->getDateEmbauche() ? $personal->getDateEmbauche()->format('d/m/Y') : null,
            'category' => $personal->getCategorie() ? $personal->getCategorie()->getLibelle() : null,
        ];

        return new JsonResponse($data);
    }

    #[Route('/index/api', name: 'index_api', methods: ['GET'])]
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
                'totalDays' => $item['totalDays'],
                'status' => $item['status'],
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
        return $this->render('/conge/index.html.twig', [
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
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $current_user
         */

        $current_user = $this->getUser();
        $newConge = new Conge();
        $forms = $this->createForm(CongeType::class, $newConge);
        $forms->handleRequest($request);
        if ($forms->isSubmitted() && $forms->isValid()) {

            $newConge->setIsConge(false);
            $newConge->setStatus('En attente');
            $entityManager->persist($newConge);
            $entityManager->flush();

            flash()->addSuccess('Congé ajouter avec succès.');
            return $this->redirectToRoute('conge_index');
        }

        return $this->render('/conge/new.html.twig', [
            'form' => $forms->createView(),
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
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $current_user
         */
        $current_user = $this->getUser();

        $forms = $this->createForm(CongeType::class, $conge);
        $forms->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $conge->setUser($current_user);
            $entityManager->persist($conge);
            $entityManager->flush();
            flash()->addSuccess('Congé modifier avec succès.');
            return $this->redirectToRoute('conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('/conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $forms->createView(),
        ]);
    }
    #[Route('/{uuid}/validate', name: 'validate', methods: ['GET'])]
    public function validate(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setIsConge(true);
        $conge->setStatus('Validé');
        $entityManager->persist($conge);
        $entityManager->flush();
        flash()->addSuccess('Congé validé avec succès.');
        return $this->redirectToRoute('conge_index');
    }
    #[Route('/{uuid}/refuse', name: 'refuse', methods: ['GET'])]
    public function refuse(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setIsConge(false);
        $conge->setStatus('Refusé');
        $entityManager->persist($conge);
        $entityManager->flush();
        flash()->addSuccess('Congé refusé avec succès.');
        return $this->redirectToRoute('conge_index');
    }
 

}
