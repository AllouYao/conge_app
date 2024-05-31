<?php

namespace App\Controller;

use App\Entity\Personal;
use App\Form\PersonalType;
use App\Repository\PersonalRepository;
use App\Service\MatriculeGenerator;
use App\Utils\Status;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier/personal', name: 'personal_')]
class PersonalController extends AbstractController
{
    private PersonalRepository $personalRepository;

    public function __construct(
        PersonalRepository               $personalRepository,
    )
    {
        $this->personalRepository = $personalRepository;
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{uuid}/print', name: 'print_salary_info', methods: ['GET'])]
    public function print(
        Personal                    $personal,
    ): Response
    {
       
        $personalSalaried = $this->getPersonalSalaried()->getContent();
        $index = $personalSalaried[10];

        $today = new DateTime();
        $age = $personal->getBirthday() ? $personal->getBirthday()->diff($today)->y : '';


        return $this->render('dossier_personal/personal/print.html.twig', [
            'personals' => $personal,
            'index' => $index,
            'age' => $age,
        ]);
    }

    #[Route('/api/salaried_book/', name: 'salaried_book', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        $personal = $this->personalRepository->findPersonalSalaried();
        $personalSalaried = [];
        foreach ($personal as $value => $item) {
            $personalSalaried[] = [
                /**
                 * Information du salarié
                 */
                "index" => ++$value,
                'full_name' => $item['personal_name'] . ' ' . $item['personal_prenoms'],
                'matricule' => $item['matricule'],
                'date_embauche' => date_format($item['contrat_date_embauche'], 'd/m/Y'),
                'fonction' => $item['personal_fonction'],
                'departement' => $item['personal_service'],
                'category' => $item['categorie_name'],
                'date_naissance' => $item['personal_birthday'] ? date_format($item['personal_birthday'], 'd/m/Y') : '',
                'adresse' => $item['personal_adresse'],
                'niveau_etude' => $item['personal_niveau_formation'],
                'compte_banque' => $item['code_banque'] . ' ' . $item['numero_compte'] . ' ' . $item['rib'],
                'salaire_base' => $item['personal_salaire_base'],
                'type_contract' => $item['type_contrat'],
                'category_grade' => $item['categorie_intitule'],
                'nature_piece' => $item['personal_piece'] . '° ' . $item['personal_numero_piece'],
                'numero_cnps' => $item['personal_numero_cnps'],
                'action' => $this->generateUrl('personal_print_salary_info', ['uuid' => $item['uuid']]),
                'modifier' => $this->generateUrl('personal_edit', ['uuid' => $item['uuid']]),
                'active' => $item['active'],
                'personal_id' => $item['personal_id'],
                'all_enable' => $this->personalRepository->areAllUsersActivated(),
                'mode_paiement' => $item['mode_paiement'],
                'sursalaire' => $item['personal_sursalaire']
            ];
        }
        return new JsonResponse($personalSalaried);
    }


    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $status = $this->personalRepository->areAllUsersActivated();

        return $this->render('dossier_personal/personal/index.html.twig', [
            'status' => $status
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager,
        MatriculeGenerator     $matriculeGenerator
    ): Response
    {
        $matricule = $matriculeGenerator->generateMatricule();
        $personal = (new Personal())->setMatricule($matricule);

        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($personal);
            $entityManager->flush();
            flash()->addSuccess('Salarié enregistré avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/new.html.twig', [
            'personal' => $personal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('{uuid}/show', name: 'show', methods: ['GET'])]
    public function show(
        Personal $personal,
    ): Response
    {
        return $this->render('dossier_personal/personal/show.html.twig', [
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

        return $this->render('dossier_personal/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form->createView(),
            'editing' => true
        ]);
    }

    #[Route('/enable', name: 'enable', methods: ['POST'])]
    public function enablePersonal(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('personalEnableInput') && $request->isMethod('POST')) {

            $personalId = $request->request->get("personalEnableInput");
            $personal = $this->personalRepository->findOneBy(['id' => $personalId]);

            if ($personal) {
                $personal->setActive(true);
                $entityManager->persist($personal);
                $entityManager->flush();
                flash()->addSuccess('Salarié Activé avec succès.');
                return $this->redirectToRoute('personal_index');
            } else {
                flash()->addWarning('Action impossible !');
                return $this->redirectToRoute('personal_index');
            }

        }

        return $this->redirectToRoute('personal_index');


    }

    #[Route('/disable', name: 'disable', methods: ['POST'])]
    public function disablePersonal(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('personalDisableInput') && $request->isMethod('POST')) {

            $personalId = $request->request->get("personalDisableInput");
            $personal = $this->personalRepository->findOneBy(['id' => $personalId]);

            if ($personal) {

                $personal->setActive(false);
                $entityManager->persist($personal);
                $entityManager->flush();
                flash()->addSuccess('Salarié Désactivé avec succès.');
                return $this->redirectToRoute('personal_index');

            } else {

                flash()->addWarning('Action impossible !');
                return $this->redirectToRoute('personal_index');

            }

        }

        return $this->redirectToRoute('personal_index');

    }

    #[Route('/toggle/all', name: 'toggle_all', methods: ['POST'])]
    public function disableAll(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->request->has('toggleAllInput') && $request->isMethod('POST')) {

            $status = $request->request->get("toggleAllInput");

            if ($status == "on") {

                $personals = $this->personalRepository->findAll();
                foreach ($personals as $personal) {
                    $personal->setActive(true);
                    $entityManager->persist($personal);
                    $entityManager->flush();
                }

                flash()->addSuccess('Salariés Activés avec succès.');

            } else {

                $personals = $this->personalRepository->findAll();
                foreach ($personals as $personal) {
                    $personal->setActive(false);
                    $entityManager->persist($personal);
                    $entityManager->flush();
                }

                flash()->addSuccess('Salariés Désactivés avec succès.');
            }

            return $this->redirectToRoute('personal_index');
        }

        return $this->redirectToRoute('personal_index');

    }

}
