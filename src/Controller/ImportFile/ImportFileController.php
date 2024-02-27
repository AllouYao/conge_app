<?php

namespace App\Controller\ImportFile;

use App\Service\MatriculeGenerator;
use App\Entity\DossierPersonal\Salary;
use App\Form\ImportFile\ImportFileType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use App\Service\ImportFileService\ImportFileService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/file/import', name: 'import_file_')]
class ImportFileController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['GET','POST'])]
        
    public function import(Request $request, MatriculeGenerator $matriculeGenerator, ImportFileService $importFileService): Response
    {
        $form = $this->createForm(ImportFileType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //récuperer le fichier uploadé
            $file = $form->get('fileName')->getData();

            $filePath = $file->getPathname();

            if ($file == !null) {

                $importFileService->import($filePath);

                if($importFileService->success){

                    flash()->addSuccess('Importation du personnel effectuée avec succès!');
                    return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);
                }

                flash()->addDanger("Erreur d'importation personnel!");

                return $this->redirectToRoute('import_file_new', [], Response::HTTP_SEE_OTHER);


            }


		

	}

		return $this->render('import_file/file.html.twig', [
			'form' => $form->createView(),
		]);

    }
    
   



   
}
