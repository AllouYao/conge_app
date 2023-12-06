<?php

namespace App\Controller\DossierPersonal;

use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\Salary;
use App\Form\DossierPersonal\PersonalType;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Service\SalaryImpotsService;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal', name: 'personal_')]
class PersonalController extends AbstractController
{
    private PersonalRepository $personalRepository;

    public function __construct(PersonalRepository $personalRepository)
    {
        $this->personalRepository = $personalRepository;
    }

    #[Route('/{uuid}/print', name: 'print_salary_info', methods: ['GET'])]
    public function print(Personal $personal): Response
    {
        $accountNumber  = null;
        $accountBanque = $personal->getAccountBanks();
        foreach ($accountBanque as $value) {
            $accountNumber = $value->getCode() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
        }
        $personalSalaried = $this->getPersonalSalaried()->getContent();
        $index = $personalSalaried[10];

        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $dateFin = $personal->getContract()->getDateFin();
        $today = new \DateTime();
        $anciennete = $dateEmbauche->diff($today)->y;
        $age = $personal->getBirthday()->diff($today)->y;
        $dureeContrat = $dateEmbauche->diff($dateFin)->m;

        $numberEnfant = $personal->getChargePeople()->count();


        return $this->render('dossier_personal/personal/print.html.twig', [
            'personals' => $personal,
            'accountBanque' => $accountNumber,
            'index' => $index,
            'anciennete' => $anciennete,
            'age' => $age,
            'dureeContrat' => $dureeContrat,
            'nombreEnfant' => $numberEnfant
        ]);
    }

    #[Route('/api/salaried_book/', name: 'salaried_book', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        $personal = $this->personalRepository->findPersonalSalaried();
        $personalSalaried = [];
        foreach ($personal as $value => $item) {
            $dateEmbauche = $item['contrat_date_embauche'];
            $today = new \DateTime();
            $anciennete = $dateEmbauche->diff($today);
            $ancienneteEnMois = $anciennete->y * 12 + $anciennete->m;
            $personalSalaried[] = [
                /**
                 * Information du salarié
                 */
                "index" => ++$value,
                'full_name' => $item['personal_name'] . ' ' . $item['personal_prenoms'],
                'matricule' => $item['matricule'],
                'date_embauche' => date_format($item['contrat_date_embauche'], 'd/m/Y'),
                'fonction' => $item[''] ?? 'Aucune information',
                'departement' => $item[''] ?? 'Aucune information',
                'category' => $item['categorie_name'],
                'date_naissance' => date_format($item['personal_birthday'], 'd/m/Y'),
                'adresse' => $item['personal_adresse'],
                'niveau_etude' => $item['personal_niveau_formation'],
                'compte_banque' => $item['code_banque'] . ' ' . $item['numero_compte'] . ' ' . $item['rib'],
                'salaire_base' => $item['personal_salaire_base'],
                'type_contract' => $item['type_contrat'],
                'taux_horaire' => $item[''] ?? 'Aucune information',
                'anciennete_mois' => $ancienneteEnMois,
                'nom_banque' => $item ['name_banque'],
                'category_grade' => $item['categorie_intitule'],
                'nature_piece' => $item['personal_piece'] . '° ' . $item['personal_numero_piece'],
                'numero_cnps' => $item['personal_numero_cnps'],
                'action' => $this->generateUrl('personal_print_salary_info', ['uuid' => $item['uuid']])
            ];
        }
        return new JsonResponse($personalSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $personal = $this->personalRepository->findPersonalSalaried();

        return $this->render('dossier_personal/personal/index.html.twig', [
            'personals' => $personal
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SalaryInterface $salary): Response
    {
        $personal = new Personal();
        $salaire = (new Salary());
        $contract = (new Contract());
        $personal->setSalary($salaire)->setContract($contract);
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($personal);
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }
            /** Service pour le calcule des impôts sur salaire du salarié et aussi celui dû par l'employeur */
            $salary->chargePersonal($personal);
            $salary->chargeEmployeur($personal);

            $entityManager->flush();
            flash()->addSuccess('Salarié enregistré avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/new.html.twig', [
            'personal' => $personal,
            'form' => $form,
        ]);
    }

    #[Route('{uuid}/show', name: 'show', methods: ['GET'])]
    public function show(Personal $personal, ChargePersonalsRepository $chargePersonalsRepository, ChargeEmployeurRepository $chargeEmployeurRepository): Response
    {
        return $this->render('dossier_personal/personal/show.html.twig', [
            'personal' => $personal,
            'charge_personal' => $chargePersonalsRepository->findOneBy(['personal' => $personal]),
            'charge_employeur' => $chargeEmployeurRepository->findOneBy(['personal' => $personal])
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personal $personal, EntityManagerInterface $entityManager, SalaryInterface $salary): Response
    {
        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }

            /** Service pour le calcule des impôts sur salaire du salarié et aussi celui dû par l'employeur */
            $salary->chargePersonal($personal);
            $salary->chargeEmployeur($personal);

            $entityManager->flush();
            flash()->addSuccess('Salarié modifier avec succès.');
            return $this->redirectToRoute('personal_show', ['uuid' => $personal->getUuid()]);
        }

        return $this->render('dossier_personal/personal/edit.html.twig', [
            'personal' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }

    #[Route('/{uuid}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Personal $personal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $personal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($personal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('personal_index', [], Response::HTTP_SEE_OTHER);
    }
}
