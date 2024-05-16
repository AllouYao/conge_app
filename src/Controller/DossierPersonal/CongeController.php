<?php

namespace App\Controller\DossierPersonal;

use App\Entity\DevPaie\CongePartiel;
use App\Entity\DossierPersonal\Conge;
use App\Entity\DossierPersonal\Personal;
use App\Entity\User;
use App\Form\DossierPersonal\CongeType;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\OldCongeRepository;
use App\Service\CongeService;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier/personal/conge', name: 'conge_')]
class CongeController extends AbstractController
{

    private CongeRepository $congeRepository;
    private OldCongeRepository $oldCongeRepository;
    private CongeService $congeService;

    public function __construct(
        CongeRepository $congeRepository, OldCongeRepository $oldCongeRepository,
        CongeService $congeService
    )
    {
        $this->congeRepository = $congeRepository;
        $this->oldCongeRepository = $oldCongeRepository;
        $this->congeService = $congeService;
    }


    #[Route('/api/conge_book/', name: 'api_book', methods: ['GET'])]
    public function getCongesSalaried(): JsonResponse
    {
        $conges = $this->congeRepository->findConge();
        $congeSalaried = [];
        foreach ($conges as $conge => $item) {
            $link = $this->generateUrl('conge_edit', ['uuid' => $item['uuid']]);
            $modifier = $item['en_conge'] === true ? $link : null;
            $dateDebut = $item['depart']; 
            $dateRetour = $item['retour'];
            $congeSalaried[] = [
                'index' => ++$conge,
                'full_name' => $item['nom'] . ' ' . $item['prenoms'],
                'date_depart' => date_format($dateDebut, 'd/m/Y'),
                'date_retour' => date_format($dateRetour, 'd/m/Y'),
                'conges_annuel_jour' => $item['totalDays'],
                'conges_jour_pris' => $item['days'],
                'dernier_conge' => $item['dernier_retour']? date_format($item['dernier_retour'], 'd/m/Y'):"N/A",
                'salaire_moyen' => $item['salaire_moyen'],
                'allocation_annuel' => $item['allocation_conge'],
                'status' => $item['en_conge'] === true ? 'OUI' : 'NON',
                'jour_restant' => $item['remainingVacation'],
                'date_reprise' => $item['dateReprise'],
                'modifier' => $modifier 
            ];
        }
        return new JsonResponse($congeSalaried);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CongeRepository $congeRepository): Response
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM Y");
        $today = Carbon::now();
        $date = $formatter->format($today);
        return $this->render('dossier_personal/conge/index.html.twig', [
            'conges' => $congeRepository->findAll(),
            'date' => $date
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager,
        CongeService           $congeService,
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $current_user
         */

        $current_user = $this->getUser();
        $conge = new Conge();
        $forms = $this->createForm(CongeType::class, $conge);
        $forms->handleRequest($request);
        if ($forms->isSubmitted() && $forms->isValid()) {
            $personal = $conge->getPersonal();

            $last_conge = $congeRepository->getLastCongeByID($personal->getId(), false);
            $historique_conge = $this->oldCongeRepository->findOneByPerso($personal->getId()); 

            /** Verifier si le salarié sélectionner est déjà en congés */
            $conge_active = $congeRepository->getLastCongeByID($personal->getId(), true);
            if($conge_active) {
                flash()->addInfo('Mr/Mdm' . $personal->getFirstName() . ' ' . $personal->getLastName() . " 
                est actuellement en congés n'est donc pas éligible pour une acquisition de congés.");
                return $this->redirectToRoute('conge_index');
            }

            $last_date_retour = null;

           
            /** Verifier si le salarié sélectionner a deja pris un congé pour la meme annnée */

            $lasConge = $this->congeRepository->findOneBy([
                "personal"=>$personal,
                "complete"=>false
            ]);


            $lastCongeDate = $lasConge?->getDateDepart();
            $currentCongeDate = $conge?->getDateDepart();

            if( $lasConge && ($lastCongeDate->format("Y") == $currentCongeDate->format("Y"))){
                $congeService->congesPayeInSameYear($conge, $lasConge);
            }elseif ($last_conge or $historique_conge) {
                $last_date_retour = $last_conge ? $last_conge->getDateRetour() or $historique_conge->getDateRetour() : null;
                $congeService->congesPayeByLast($conge);

            } else {
                $congeService->congesPayeFirst($conge);
            }
            if (!$congeService->success) {

                flash()->addInfo($congeService->messages);
                return $this->redirectToRoute('conge_new');
 
            }

            $conge
                ->setDateDernierRetour($last_date_retour)
                ->setIsConge(true)
                ->setUser($current_user);
  
            $entityManager->persist($conge);
            $entityManager->flush();

            flash()->addSuccess('Congé ajouter avec succès.');
            return $this->redirectToRoute('conge_index');
        }

        return $this->render('dossier_personal/conge/new.html.twig', [
            'conge' => $conge,
            'form' => $forms->createView(),
        ]);
    }


    /**
     * @throws Exception
     */
    #[Route('/{uuid}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Conge                  $conge,
        EntityManagerInterface $entityManager,
        CongeService           $congeService,
        CongeRepository        $congeRepository
    ): Response
    {
        /**
         * @var User $current_user
         */
        $current_user = $this->getUser();

        $forms = $this->createForm(CongeType::class, $conge);
        $forms->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $personal = $conge->getPersonal();
            $last_conge = $congeRepository->getLastCongeByID($personal->getId(), false);
            $historique_conge = $this->oldCongeRepository->findOneByPerso($personal->getId());
            if ($last_conge or $historique_conge) {
                $congeService->congesPayeByLast($conge); 
            } else { 
                $congeService->congesPayeFirst($conge);
            }
            if (!$congeService->success) {
                flash()->addInfo($congeService->messages);
                return $this->redirectToRoute('conge_edit', ['uuid' => $conge->getUuid()]);
            }
            $conge->setUser($current_user);
            $entityManager->persist($conge);
            $entityManager->flush();
            flash()->addSuccess('Congé modifier avec succès.');
            return $this->redirectToRoute('conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dossier_personal/conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $forms->createView(),
        ]);
    }
   
 

}
