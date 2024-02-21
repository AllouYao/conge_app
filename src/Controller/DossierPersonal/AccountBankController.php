<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\AccountBank;
use App\Form\DossierPersonal\AccountType;
use App\Repository\DossierPersonal\AccountBankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/account_bank', name: 'personal_account_bank_')]
class AccountBankController extends AbstractController
{

    #[Route('/api_account_bank', name: 'api_account_bank', methods: ['GET'])]
    public function apiAccountBank(AccountBankRepository $accountBankRepository): JsonResponse
    {
        $accountBanks = $accountBankRepository->findAll();
        $apiAccountBank = [];

        foreach ($accountBanks as $accountBank) {
            $apiAccountBank[] = [
                'matricule' => $accountBank->getPersonal()->getMatricule(),
                'name' => $accountBank->getPersonal()->getFirstName(),
                'last_name' => $accountBank->getPersonal()->getLastName(),
                'date_naissance' => date_format($accountBank->getPersonal()->getBirthday(), 'd/m/Y'),
                'categorie_salarie' => '(' . $accountBank->getPersonal()->getCategorie()->getCategorySalarie()->getName() . ')'
                    . '-' . $accountBank->getPersonal()->getCategorie()->getIntitule(),
                'date_embauche' => date_format($accountBank->getPersonal()->getContract()->getDateEmbauche(), 'd/m/Y'),
                'number_compte' => $accountBank->getNumCompte(),
                'bank' => $accountBank->getBankId(),
                'rib' => $accountBank->getRib(),
                'date_creation' => date_format($accountBank->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('personal_account_bank_edit', ['uuid' => $accountBank->getUuid()])
            ];
        }
        return new JsonResponse($apiAccountBank);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('dossier_personal/account_bank/index.html.twig');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $manager
    ): Response
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        
        $form = $this->createForm(AccountType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $accountBanks = $form->get('accountBanks')->getData();
            $personal = $form->get('personal')->getData();
            foreach ($accountBanks as $accountBank) {
                $accountBank->setPersonal($personal);
                $accountBank->setUser($currentUser);
                $manager->persist($accountBank);
            }
            $manager->flush();
            flash()->addSuccess('Compte banque crée avec succès.');
            return $this->redirectToRoute('personal_account_bank_index');
        }
        return $this->render('dossier_personal/account_bank/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AccountBank $accountBank, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AccountType::class, [
            'personal' => $accountBank->getPersonal(),
            'accountBanks' => $accountBank->getPersonal()->getAccountBanks()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($accountBank as $accountB) {
                $accountB->setPersonal($accountBank->getPersonal());
                $manager->persist($accountB);
            }
            $manager->flush();
            flash()->addSuccess('Compte banque modifié avec succès.');
            return $this->redirectToRoute('personal_account_bank_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/account_bank/edit.html.twig', [
            'personal' => $accountBank->getPersonal(),
            'form' => $form,
            'editing' => true
        ]);
    }
}
