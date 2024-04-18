<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Entity\DossierPersonal\Salary;
use App\Form\DossierPersonal\PersonalType;
use App\Repository\DossierPersonal\DetailPrimeSalaryRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\PersonalRepository;
use App\Repository\Settings\PrimesRepository;
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

    public function __construct(PersonalRepository $personalRepository)
    {
        $this->personalRepository = $personalRepository;
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/{uuid}/print', name: 'print_salary_info', methods: ['GET'])]
    public function print(
        Personal                    $personal,
        DetailSalaryRepository      $detailSalaryRepository,
        PrimesRepository            $primesRepository,
        DetailPrimeSalaryRepository $detailPrimeSalaryRepository
    ): Response
    {
        $accountNumber = $nameBanque = null;
        $accountBanque = $personal->getAccountBanks();
        foreach ($accountBanque as $value) {
            $accountNumber = $value->getCode() . ' ' . $value->getCodeAgence() . ' ' . $value->getNumCompte() . ' ' . $value->getRib();
            $nameBanque = $value->getName();
        }
        $personalSalaried = $this->getPersonalSalaried()->getContent();
        $index = $personalSalaried[10];

        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $today = new DateTime();
        $anciennete = (int)$personal->getOlder();
        $age = $personal->getBirthday() ? $personal->getBirthday()->diff($today)->y : '';
        $dureeContrat = round(($today->diff($dateEmbauche)->days) / 30);

        $numberEnfant = $personal->getChargePeople()->count();

        $primePanier = $primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
        $primeSalissure = $primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
        $primeTT = $primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
        $primeOutil = $primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
        $primeFonction = $primesRepository->findOneBy(['code' => Status::PRIME_FONCTION]);
        $primeLogement = $primesRepository->findOneBy(['code' => Status::PRIME_LOGEMENT]);
        $primeRendement = $primesRepository->findOneBy(['code' => Status::PRIME_RENDEMENT]);
        $indemniteFonction = $primesRepository->findOneBy(['code' => Status::INDEMNITE_FONCTION]);
        $indemniteLogement = $primesRepository->findOneBy(['code' => Status::INDEMNITE_LOGEMENTS]);


        $amountPanier = $detailSalaryRepository->findPrimeBySalary($personal, $primePanier);
        $amountSalissure = $detailSalaryRepository->findPrimeBySalary($personal, $primeSalissure);
        $amountTT = $detailSalaryRepository->findPrimeBySalary($personal, $primeTT);
        $amountOutil = $detailSalaryRepository->findPrimeBySalary($personal, $primeOutil);
        $amountFonction = $detailPrimeSalaryRepository->findPrimeBySalaries($personal, $primeFonction);
        $amountLogement = $detailPrimeSalaryRepository->findPrimeBySalaries($personal, $primeLogement);
        $amountRendement = $detailSalaryRepository->findPrimeBySalary($personal, $primeRendement);
        $amountIndemFonction = $detailPrimeSalaryRepository->findPrimeBySalaries($personal, $indemniteFonction);
        $amountIndemLogement = $detailPrimeSalaryRepository->findPrimeBySalaries($personal, $indemniteLogement);

        $salaireBase = $personal->getSalary()->getBaseAmount();
        $sursalaire = $personal->getSalary()->getSursalaire();
        $salarieTransport = $personal->getSalary()->getPrimeTransport();
        $avantageAmount = $personal->getSalary()->getAvantage()?->getTotalAvantage();
        return $this->render('dossier_personal/personal/print.html.twig', [
            'personals' => $personal,
            'accountBanque' => $accountNumber,
            'nom_banque' => $nameBanque,
            'index' => $index,
            'anciennete' => $anciennete,
            'age' => $age,
            'dureeContrat' => $dureeContrat,
            'nombreEnfant' => $numberEnfant,
            'salaireBase' => $salaireBase ?? 0,
            'sursalaire' => $sursalaire ?? 0,
            'primePanier' => $amountPanier !== null ? (int)$amountPanier['amountPrime'] : 0,
            'primeSalissure' => $amountSalissure !== null ? (int)$amountSalissure['amountPrime'] : 0,
            'primeTT' => $amountTT !== null ? (int)$amountTT['amountPrime'] : 0,
            'primeOutil' => $amountOutil !== null ? (int)$amountOutil['amountPrime'] : 0,
            'primeRendement' => $amountRendement !== null ? (int)$amountRendement['amountPrime'] : 0,
            'primeTransport' => $salarieTransport !== 0 ? (int)$salarieTransport : 0,
            'primeLogement' => $amountLogement !== null ? (int)$amountLogement['amount'] : 0,
            'primeFonction' => $amountFonction !== null ? (int)$amountFonction['amount'] : 0,
            'indemniteFonction' => $amountIndemFonction !== null ? (int)$amountIndemFonction['amount'] : 0,
            'indemniteLogement' => $amountIndemLogement !== null ? (int)$amountIndemLogement['amount'] : 0,
            'avantageAmount' => $avantageAmount !== 0 ? (int)$avantageAmount : 0,
        ]);
    }

    #[Route('/api/salaried_book/', name: 'salaried_book', methods: ['GET'])]
    public function getPersonalSalaried(): JsonResponse
    {
        if ($this->isGranted('ROLE_RH')) {
            $personal = $this->personalRepository->findPersonalSalaried();
        } else {
            $personal = $this->personalRepository->findPersonalSalariedByEmployeRole();
        }

        $personalSalaried = [];
        foreach ($personal as $value => $item) {
            $anciennete = $item['older'];
            $ancienneteEnMois = $anciennete * 12;
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
                'anciennete_mois' => number_format($ancienneteEnMois, 2, ',', ' '),
                'nom_banque' => $item ['name_banque'],
                'category_grade' => $item['categorie_intitule'],
                'nature_piece' => $item['personal_piece'] . '° ' . $item['personal_numero_piece'],
                'numero_cnps' => $item['personal_numero_cnps'],
                'action' => $this->generateUrl('personal_print_salary_info', ['uuid' => $item['uuid']]),
                'modifier' => $this->generateUrl('personal_edit', ['uuid' => $item['uuid']]),
                'active' => $item['active'],
                'personal_id' => $item['personal_id'],
                'all_enable' => $this->personalRepository->areAllUsersActivated(),
                'mode_paiement' => $item['mode_paiement']
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
        $numContract = $matriculeGenerator->generateNumContract();
        $personal = (new Personal())->setMatricule($matricule);
        $salaire = (new Salary());
        $contract = (new Contract())->setRefContract($numContract);
        $personal
            ->setActive(true)->setSalary($salaire)
            ->setContract($contract);

        $form = $this->createForm(PersonalType::class, $personal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($personal);
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }
            foreach ($personal->getSalary()->getDetailPrimeSalaries() as $detailPrimeSalary) {
                $detailPrimeSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailPrimeSalary);
            }
            foreach ($personal->getSalary()->getDetailRetenueForfetaires() as $detailRetenueForfetaire) {
                $detailRetenueForfetaire->setSalary($personal->getSalary());
                $entityManager->persist($detailRetenueForfetaire);
            }
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
            foreach ($personal->getSalary()->getDetailSalaries() as $detailSalary) {
                $detailSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailSalary);
            }
            foreach ($personal->getSalary()->getDetailPrimeSalaries() as $detailPrimeSalary) {
                $detailPrimeSalary->setSalary($personal->getSalary());
                $entityManager->persist($detailPrimeSalary);
            }
            foreach ($personal->getSalary()->getDetailRetenueForfetaires() as $detailRetenueForfetaire) {
                $detailRetenueForfetaire->setSalary($personal->getSalary());
                $entityManager->persist($detailRetenueForfetaire);
            }
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
