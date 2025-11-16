<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Form\PersonalType;
use App\Repository\PersonalRepository;
use App\Service\MatriculeGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('personal', name: 'personal_')]
class PersonalController extends AbstractController
{
    private PersonalRepository $personalRepository;

    public function __construct(
        PersonalRepository $personalRepository,
        private EntityManagerInterface $entityManager,

    )
    {
        $this->personalRepository = $personalRepository;
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{uuid}/print', name: 'print', methods: ['GET'])]
    public function print(
        Personal $personal,
    ): Response
    {
       
        $personalSalaried = $this->getPersonalSalaried()->getContent();
        $index = $personalSalaried[10];

        return $this->render('personal/print.html.twig', [
            'personals' => $personal,
            'index' => $index,
        ]);
    }

    #[Route('/index/api', name: 'index_api', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        $personals = $this->personalRepository->findAll();
        $personalData = [];
        $index=0;
        foreach ($personals as $personal) {

            $fonctions = "";

            foreach($personal->getFonctions() as $fonction){
                $fonctions =  $fonction->getLibelle();
            }

            $personalData[] = [
                /**
                 * Information du salarié
                 */
                "index" => ++$index,
                'full_name' => $personal->getLastName() . ' ' . $personal->getFirstName() ,
                'matricule' => $personal->getMatricule() ,
                'fonction' => $fonctions,
                'departement' => $personal->getService()->getLibelle(),
                'category' => $personal->getCategorie()->getLibelle(),
                'adresse' => $personal->getAddress(),
                'action' => $this->generateUrl('personal_print', ['uuid' => $personal->getUuid()]),
                'modifier' => $this->generateUrl('personal_edit', ['uuid' => $personal->getUuid()]),
                'personal_id' => $personal->getId(),
            ];
        }
        return new JsonResponse($personalData);
    }


    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {

        return $this->render('personal/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        MatriculeGenerator     $matriculeGenerator
    ): Response
    {
        $matricule = $matriculeGenerator->generateMatricule();
        $personal = (new Personal())->setMatricule($matricule);

        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($personal);
            $this->entityManager->flush();
            flash()->addSuccess('Salarié enregistré avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('personal/new.html.twig', [
            'personal' => $personal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('{uuid}/show', name: 'show', methods: ['GET'])]
    public function show(
        Personal $personal,
    ): Response
    {
        return $this->render('personal/show.html.twig', [
            'personal' => $personal,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personal $personal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->flush();
            flash()->addSuccess('Salarié modifier avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form->createView(),
            'editing' => true
        ]);
    }

}
