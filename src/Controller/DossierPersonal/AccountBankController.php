<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DossierPersonal\Personal;
use App\Form\DossierPersonal\AccountType;
use App\Repository\DossierPersonal\AccountBankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dossier/personal/account_bank', name: 'personal_account_bank_')]
class AccountBankController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(AccountBankRepository $accountBankRepository): Response
    {
        return $this->render('dossier_personal/account_bank/index.html.twig', [
            'accountBanks' => $accountBankRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $manager
    ): Response
    {
        $form = $this->createForm(AccountType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $accountBanks = $form->get('accountBanks')->getData();
            $personal = $form->get('personal')->getData();
            foreach ($accountBanks as $accountBank) {
                $accountBank->setPersonal($personal);
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
    public function edit(Request $request, Personal $personal, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AccountType::class, [
            'personal' => $personal,
            'accountBanks' => $personal->getAccountBanks()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($personal->getAccountBanks() as $accountBank) {
                $accountBank->setPersonal($personal);
                $manager->persist($accountBank);
            }
            $manager->flush();
            flash()->addSuccess('Compte banque modifié avec succès.');
            return $this->redirectToRoute('personal_account_bank_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/account_bank/edit.html.twig', [
            'personals' => $personal,
            'form' => $form,
            'editing' => true
        ]);
    }
}
