<?php

namespace App\Controller\DossierPersonal;

use App\Contract\SalaryInterface;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\Salary;
use App\Form\DossierPersonal\PersonalType;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
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

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{uuid}/print', name: 'print_salary_info', methods: ['GET'])]
    public function print(
        Personal               $personal,
        DetailSalaryRepository $detailSalaryRepository,
        PrimesRepository       $primesRepository
    ): Response
    {
        $accountNumber = null;
        $accountBanque = $personal->getAccountBanks();
        foreach ($accountBanque as $value) {
            $accountNumber = $value->getCode() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
        }
        $personalSalaried = $this->getPersonalSalaried()->getContent();
        $index = $personalSalaried[10];

        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $dateFin = $personal->getContract()->getDateFin();
        $today = new DateTime();
        $anciennete = $today->diff($dateEmbauche)->y;
        $age = $personal->getBirthday()->diff($today)->y;
        $dureeContrat = ($dateFin->diff($dateEmbauche)->y) * 12;

        $numberEnfant = $personal->getChargePeople()->count();

        $primePanier = $primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
        $primeSalissure = $primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
        $primeTT = $primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
        $primeOutil = $primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);

        $amountPanier = $detailSalaryRepository->findPrimeBySalary($personal, $primePanier);
        $amountSalissure = $detailSalaryRepository->findPrimeBySalary($personal, $primeSalissure);
        $amountTT = $detailSalaryRepository->findPrimeBySalary($personal, $primeTT);
        $amountOutil = $detailSalaryRepository->findPrimeBySalary($personal, $primeOutil);

        $salaireBase = $personal->getSalary()->getBaseAmount();
        $salarieTransport = $personal->getSalary()->getPrimeTransport();
        $salarieLogement = $personal->getSalary()->getPrimeLogement();
        $salarieFonction = $personal->getSalary()->getPrimeFonction();
        $avantageAmount = $personal->getSalary()->getAvantage()?->getTotalAvantage();
        return $this->render('dossier_personal/personal/print.html.twig', [
            'personals' => $personal,
            'accountBanque' => $accountNumber,
            'index' => $index,
            'anciennete' => $anciennete,
            'age' => $age,
            'dureeContrat' => $dureeContrat,
            'nombreEnfant' => $numberEnfant,
            'salaireBase' => $salaireBase ?? 0,
            'primePanier' => $amountPanier !== null ? (int)$amountPanier['amountPrime'] : 0,
            'primeSalissure' => $amountSalissure !== null ? (int)$amountSalissure['amountPrime'] : 0,
            'primeTT' => $amountTT !== null ? (int)$amountTT['amountPrime'] : 0,
            'primeOutil' => $amountOutil !== null ? (int)$amountOutil['amountPrime'] : 0,
            'primeTransport' => $salarieTransport !== 0 ? (int)$salarieTransport : 0,
            'primeLogement' => $salarieLogement !== 0 ? (int)$salarieLogement : 0,
            'primeFonction' => $salarieFonction !== 0 ? (int)$salarieFonction : 0,
            'avantageAmount' => $avantageAmount !== 0 ? (int)$avantageAmount : 0,
        ]);
    }

    #[Route('/api/salaried_book/', name: 'salaried_book', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        $personal = $this->personalRepository->findPersonalSalaried();
        $personalSalaried = [];
        foreach ($personal as $value => $item) {
            $dateEmbauche = $item['contrat_date_embauche'];
            $today = new DateTime();
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
                'fonction' => $item['personal_fonction'],
                'departement' => $item['personal_service'],
                'category' => $item['categorie_name'],
                'date_naissance' => date_format($item['personal_birthday'], 'd/m/Y'),
                'adresse' => $item['personal_adresse'],
                'niveau_etude' => $item['personal_niveau_formation'],
                'compte_banque' => $item['code_banque'] . ' ' . $item['numero_compte'] . ' ' . $item['rib'],
                'salaire_base' => $item['personal_salaire_base'],
                'type_contract' => $item['type_contrat'],
                'anciennete_mois' => $ancienneteEnMois,
                'nom_banque' => $item ['name_banque'],
                'category_grade' => $item['categorie_intitule'],
                'nature_piece' => $item['personal_piece'] . '° ' . $item['personal_numero_piece'],
                'numero_cnps' => $item['personal_numero_cnps'],
                'action' => $this->generateUrl('personal_print_salary_info', ['uuid' => $item['uuid']]),
                'modifier' => $this->generateUrl('personal_edit', ['uuid' => $item['uuid']])
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
