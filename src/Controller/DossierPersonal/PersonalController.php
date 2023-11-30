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

    #[Route('/api/salaried_book/', name: 'salaried_book', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        $personal = $this->personalRepository->findPersonalSalaried();
        $personalSalaried = [];
        foreach ($personal as $item) {
            $personalSalaried[] = [
                /**
                 * Information du salarié
                 */
                'matricule' => $item['matricule'],
                'full_name' => $item['personal_name'] . ' ' . $item['personal_prenoms'],
                'genre' => $item['personal_genre'],
                'date_naissance' => date_format($item['personal_birthday'], 'd/m/Y'),
                'lieu_naissance' => $item['personal_lieu_naiss'],
                'numero_cnps' => $item['personal_numero_cnps'],
                'nature_piece' => $item['personal_piece'],
                'numero_piece' => $item['personal_numero_piece'],
                'etat_civil' => $item['personal_etat_civil'],
                'mode_paiement' => $item['personal_mode_paiement'],
                'anciennete' => $item['personal_anciennete'],
                /**
                 * Coordonnees du salarie
                 */
                'adresse' => $item['personal_adresse'],
                'telephone' => $item['personal_telephone'],
                'email' => $item['personal_email'],
                /**
                 * Catégorie socio professionnel
                 */
                'category' => '( ' . $item['categorie_name'] . ' ) - ' . $item['categorie_intitule'],
                'niveau_formation' => $item['personal_niveau_formation'],
                /**
                 * Contrat du salarié
                 */
                'type_contract' => $item['type_contrat'],
                'date_embauche' => date_format($item['contrat_date_embauche'], 'd/m/Y'),
                'date_effet' => date_format($item['contrat_date_effet'], 'd/m/Y'),
                'date_fin' => date_format($item['contrat_date_fin'], 'd/m/Y'),
                'temps_contractuel' => $item['temps_contractuel'],
                /**
                 * Salaire du salarié
                 */
                'salaire_base' => $item['personal_salaire_base'],
                'sursalaire' => $item['personal_sursalaire'],
                'prime_transport' => $item['personal_prime_transport'],
                'prime_fonction' => $item['personal_prime_fonction'],
                'prime_logement' => $item['personal_prime_logement'],
                'prime_non_juridique' => $item['personal_total_prime_juridique'],
                'aventage_nature' => $item['personal_avantage_piece'] . ' - ' . $item['personal_avantage_total_amount'],
                'salaire_brut' => $item['personal_salaire_brut'],
                'salaire_imposable' => $item['personal_salaire_imposable'],
                /**
                 * Charge salarie
                 */
                'nombre_part' => $item['charge_personal_nombre_part'],
                'montant_its' => $item['charge_personal_its'],
                'montant_cmu' => $item['charge_personal_cmu'],
                'montant_cnps' => $item['charge_personal_cnps'],
                'total_charge_personal' => $item['total_charge_personal'],
                /**
                 * Charge employeur
                 */
                'montant_is' => $item['charge_employeur_is'],
                'montant_fdfp' => $item['charge_employeur_fdfp'],
                'montant_cr' => $item['charge_employeur_cr'],
                'montant_pf' => $item['charge_employeur_pf'],
                'montant_at' => $item['charge_employeur_at'],
                'montant_emp_cmu' => $item['charge_employeur_cmu'],
                'total_retenu_cnps' => $item['total_retenu_cnps'],
                'total_charge_employeur' => $item['total_charge_employeur'],
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

    #[Route('/{uuid}/show', name: 'show', methods: ['GET'])]
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
