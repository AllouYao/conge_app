<?php

namespace App\Controller\OlConge;

use App\Utils\Status;
use App\Form\OldConge\OldCongeType;
use App\Entity\DossierPersonal\OldConge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DossierPersonal\OldCongeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/old/conge', name: 'old_conge_')]
class OldCongeController extends AbstractController
{
    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        return $this->render('dossier_personal/old_conge/index.html.twig');
    }


    #[Route('/api', name: 'api')]
    public function apiAcompte(OldCongeRepository $oldCongeRepository): JsonResponse
    {
        $oldConges = $oldCongeRepository->findAll();
        $data = [];

        foreach ($oldConges as $oldConge) {
            $data[] = [
                'id' => $oldConge->getId(),
                'date' => date_format($oldConge->getDateRetour(), 'd/m/y'),
                'matricule' => $oldConge->getPersonal()->getMatricule(),
                'nom_prenom' => $oldConge->getPersonal()->getFirstName() . ' ' . $oldConge->getPersonal()->getLastName(),
                'genre' => $oldConge->getPersonal()->getGenre(),
                'salaryAverage' => $oldConge->getSalaryAverage(),
                'modifier' => $this->generateUrl('old_conge_edit', ['uuid' => $oldConge->getUuid()])
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $oldConge = new OldConge();
        $form = $this->createForm(OldCongeType::class, $oldConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($oldConge);
            $this->manager->flush();
            flash()->addSuccess('Ancien Congé enregistré avec succès');
            return $this->redirectToRoute('old_conge_index');
        }
        return $this->render('dossier_personal/old_conge/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods:['GET','POST'])]
    public function edit(OldConge $oldconge,Request $request): Response
    {
        $form = $this->createForm(OldCongeType::class,$oldconge);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $this->manager->persist($oldconge);
            $this->manager->flush();

            flash()->addSuccess('Ancien Congé modifié avec succès');
            return $this->redirectToRoute('old_conge_index');
        }
        return $this->render('dossier_personal/old_conge/edit.html.twig',[
            'form' => $form
        ]);
    }
}
