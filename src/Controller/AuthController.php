<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AuthUserType;
use App\Form\ProfileEditType;
use App\Form\ProfileShowType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/auth/user', name: 'auth_user_')]
class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher,
    )
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;

    }

    #[Route('/api', name: 'api', methods: ['GET'])]
    public function api_utilisateur(): JsonResponse
    {
        $allUsers = $this->userRepository->findAll();
        $users = [];

        if (!$allUsers) {
            return $this->json(['data' => []]);
        }

        foreach ($allUsers as $user) {
            $users[] = [
                'username' => $user->getusername(),
                'email' => $user->getEmail(),
                'date_creation' => date_format($user->getCreatedAt(), 'd/m/Y'),
                'modifier' => $this->generateUrl('auth_user_edit', ['uuid' => $user->getUuid()])
            ];
        }
        return new JsonResponse($users);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('auth/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(AuthUserType::class,$user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $passwordToHash = "password";

            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $passwordToHash)
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            flash()->addSuccess('Utilisateur créer avec succès.');
            return $this->redirectToRoute('auth_user_index', [], Response::HTTP_SEE_OTHER);
        }


        return $this->render('auth/user/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        $form = $this->createForm(AuthUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->entityManager->persist($user); 
            $this->entityManager->flush();
            flash()->addSuccess('Utilisateur modifié avec succès.');
            return $this->redirectToRoute('auth_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('auth/user/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/profile', name: 'edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashPassword = $this->userPasswordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashPassword);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            flash()->addSuccess('Compte utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('auth/user/edit_profile.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/profile/show', name: 'show_profile', methods: ['GET', 'POST'])]
    public function showProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileShowType::class, $user);
        $form->handleRequest($request);

        return $this->render('auth/user/profile.html.twig', [
            'form' => $form,
        ]);
    }
}