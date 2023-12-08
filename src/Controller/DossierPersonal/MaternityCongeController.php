<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Conge;
use App\Form\DossierPersonal\CongeMaternityType;
use App\Form\DossierPersonal\CongeType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Service\SalaryImpotsService;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', name: 'maternity_conge_')]
class MaternityCongeController extends AbstractController
{
    private PersonalRepository $personalRepository;
    private CongeRepository $congeRepository;
    private SalaryImpotsService $impotsService;

    public function __construct(PersonalRepository $personalRepository, CongeRepository $congeRepository, SalaryImpotsService $impotsService)
    {
        $this->personalRepository = $personalRepository;
        $this->congeRepository = $congeRepository;
        $this->impotsService = $impotsService;
    }


    #[Route('/api/maternity_conge_book/', name: 'api_maternity_book', methods: ['GET'])]
    public function maternityCongeBook(): JsonResponse
    {
        $personals = $this->personalRepository->findAllWomanPersonal();
        $conges = $this->congeRepository->findConge(Status::CONGE_MATERNITY);
        $perso = null;
        $congeSalaried = [];
        foreach ($personals as $personal) {
            $perso = $personal;
        }
        foreach ($conges as $conge => $item) {
            $nbJourCongeAnnuel = $this->impotsService->getCongeMonth($perso);
            $congeSalaried[] = [
                'index' => ++$conge,
                'full_name' => $item['nom'] . ' ' . $item['prenoms'],
                'date_depart' => date_format($item['depart'], 'd/m/y'),
                'date_retour' => date_format($item['retour'], 'd/m/y'),
                'conges_annuel_jour' => $nbJourCongeAnnuel['conge_day'],
                'dernier_conge' => date_format($item['dernier_retour'], 'd/m/y'),
                'salaire_moyen' => $item['salaire_moyen'],
                'allocation_annuel' => $item['allocation_conge'],
                'commentaire' => $item['commentaire'],
                'en_conge_?' => $item['en_conge'] === true ? 'OUI' : 'NOM',
                'action' => $this->generateUrl('maternity_conge_index', ['uuid' => $item['uuid']])
            ];
        }
        return new JsonResponse($congeSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CongeRepository $congeRepository): Response
    {
        return $this->render('dossier_personal/conge_maternity/index.html.twig', [
            'conges' => $congeRepository->findAll(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $conge = new Conge();
        $form = $this->createForm(CongeMaternityType::class, $conge);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $dateDernierRetour = $form->get('dateRetour')->getData();
            $personal = $form->get('personal')->getData();
            $active = $this->congeRepository->active($personal);
            if ($active) {
                $this->addFlash('error', 'Madame ' . $personal->getFirstName() . ' ' . $personal->getLastName() . ' n\'est pas encore de retour du congé précédent.');
                return $this->redirectToRoute('maternity_conge_index');
            }
            $elementConge = $this->impotsService->getAllocation($personal);
            $salaireMoyen = $elementConge['salaire_moyen'];
            $allocationAnnuel = $elementConge['allocation_conge_annuel'];
            $conge
                ->setDateDernierRetour($dateDernierRetour)
                ->setSalaireMoyen($salaireMoyen)
                ->setAllocationConge($allocationAnnuel)
                ->setTypeConge(Status::CONGE_MATERNITY)
                ->setIsConge(true);
            $entityManager->persist($conge);
            $entityManager->flush();
            flash()->addSuccess('Congé de maternité planifié avec succès.');
            return $this->redirectToRoute('maternity_conge_index');
        }

        return $this->render('dossier_personal/conge_maternity/new.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }
}