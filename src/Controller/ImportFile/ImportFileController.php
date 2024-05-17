<?php

namespace App\Controller\ImportFile;

use App\Form\ImportFile\ImportFileType;
use App\Service\ImportFileService\ImportFileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    #[Route('/new/conge', name: 'new_conge', methods: ['GET', 'POST'])]
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


}
