<?php

namespace App\Controller\OlConge;

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
        $old_conges = $oldCongeRepository->findAll();
        $data_old_conges = [];

        foreach ($old_conges as $old_conge) {
            $data_old_conges[] = [
                'id' => $old_conge->getId(),
                'date' => date_format($old_conge->getDateRetour(), 'd/m/Y'),
                'date_creation' => date_format($old_conge->getCreatedAt(), 'd/m/Y'),
                'matricule' => $old_conge->getPersonal()->getMatricule(),
                'nom_prenom' => $old_conge->getPersonal()->getFirstName() . ' ' . $old_conge->getPersonal()->getLastName(),
                'genre' => $old_conge->getPersonal()->getGenre(),
                'salaryAverage' => $old_conge->getSalaryAverage(),
                'modifier' => $this->generateUrl('old_conge_edit', ['uuid' => $old_conge->getUuid()])
            ];
        }
        return new JsonResponse($data_old_conges);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function news(Request $request): Response
    {

        $old_conge = new OldConge();
        $forms = $this->createForm(OldCongeType::class, $old_conge);
        $forms->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $this->manager->persist($old_conge);
            $this->manager->flush();
            flash()->addSuccess('Ancien Congé enregistré avec succès');
            return $this->redirectToRoute('old_conge_index');
        }
        return $this->render('dossier_personal/old_conge/new.html.twig', [
            'form' => $forms->createView()
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods:['GET','POST'])]
    public function edit(OldConge $old_conge,Request $request): Response
    {
        $forms = $this->createForm(OldCongeType::class,$old_conge);
        $forms->handleRequest($request);

        if($forms->isSubmitted() && $forms->isValid())
        {
            $this->manager->persist($old_conge);
            $this->manager->flush();

            flash()->addSuccess('Ancien Congé modifié avec succès');
            return $this->redirectToRoute('old_conge_index');
        }
        return $this->render('dossier_personal/old_conge/edit.html.twig',[
            'form' => $forms
        ]);
    }
}
