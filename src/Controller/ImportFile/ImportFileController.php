<?php

namespace App\Controller\ImportFile;

use App\Form\ImportFile\ImportFileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ImportFileService\ImportFileService;
use App\Repository\DossierPersonal\PersonalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/file/import', name: 'import_file_')]
class ImportFileController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function importPersonal(Request $request, ImportFileService $importFileService): Response
    {
        $form = $this->createForm(ImportFileType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //récuperer le fichier uploadé
            $file = $form->get('fileName')->getData();

            $filePath = $file->getPathname();

            if ($file == !null) {
                $importFileService->importPersonal($filePath);
                if ($importFileService->success) {

                    flash()->addSuccess('Importation de la fiche du personnel effectuée avec succès!');
                    return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);
                }
                flash()->addError("Erreur lors de l'importation de la fiche du personnel!");
                flash()->addInfo('Veuillez corriger votre fichier et ré-essaiyer s\'il vous plaît merci !');
                return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('import_file/salary_file.html.twig', [
            'form' => $form->createView(),
        ]);

    }
    #[Route('/historic/conge', name: 'historic_conge', methods: ['GET', 'POST'])]
    public function importConge(Request $request, ImportFileService $importFileService): Response
    {
        $form = $this->createForm(ImportFileType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //récuperer le fichier uploadé
            $file = $form->get('fileName')->getData();

            $filePath = $file->getPathname();

            if ($file == !null) {
                $importFileService->importPersonal($filePath);
                if ($importFileService->success) {

                    flash()->addSuccess('Importation de la fiche du personnel effectuée avec succès!');
                    return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);
                }
                flash()->addError("Erreur lors de l'importation de la fiche du personnel!");
                flash()->addInfo('Veuillez corriger votre fichier et ré-essaiyer s\'il vous plaît merci !');
                return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('import_file/conge_file.html.twig', [
            'form' => $form->createView(),
        ]);

    }
    #[Route('/historic/conge/model/download', name: 'historic_conge_download', methods: ['GET', 'POST'])]
    public function dowloadModel(): Response
    {
        return $this->render('import_file/model_historic_conge.html.twig', [
        ]);
    }
    #[Route('/api/historic/conge', name: 'api_historic_conge')]
    public function apiDataModel(PersonalRepository $personalRepository): JsonResponse
    {
        $personals = $personalRepository->findBy(["active"=>true]);
        $dataPersonals = [];

        foreach ($personals as $personal) {
            $dataPersonals[] = [
                'matricule' => $personal->getMatricule(),
                'nom' => $personal->getFirstName(),
                'prenom' =>$personal->getLastName(),
                'salaire_moyen',
                'stock',
                'date_retour',
            ];
        }
        return new JsonResponse($dataPersonals);
    }


}
