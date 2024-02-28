<?php

namespace App\Service\ImportFileService;

use App\Utils\Status;
use App\Service\MatriculeGenerator;
use App\Entity\DossierPersonal\Salary;
use App\Entity\DossierPersonal\Absence;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Entity\DossierPersonal\AccountBank;
use App\Entity\DossierPersonal\ChargePeople;
use App\Repository\Settings\PrimesRepository;
use App\Repository\Settings\CategoryRepository;
use App\Entity\DossierPersonal\DetailPrimeSalary;
use App\Entity\DossierPersonal\DetailSalary;
use App\Repository\DossierPersonal\AbsenceRepository;

class ImportFileService
{
    public bool $success = false;
    public function __construct(
        private MatriculeGenerator $matriculeGenerator,
        private CategoryRepository $categoryRepository,
        private PrimesRepository $primesRepository,
        private EntityManagerInterface $entityManager){

    }
    public function import($filePath):bool
    {
        try {

            $spreadsheet = IOFactory::load($filePath);
            $reader = new Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            foreach ($worksheet as $row) {
                $matricule = $this->matriculeGenerator->generateMatricule();
                $numCNPS = $this->matriculeGenerator->generateNumCnps();
                $numContract = $this->matriculeGenerator->generateNumContract();

                $personal = new Personal();
                $salary = new Salary();
                $contract = new Contract();
                $accountBank = new AccountBank();


                $personal->setMatricule($matricule);

                $personal->setFirstName($row['B'] ?? 'N/A' ); // Nom
                $personal->setLastName($row['C'] ?? 'N/A'); // Prenom

                //Genre
                if($row['E']=="HOMME"){
                    $personal->setGenre('HOMME');
                }
                elseif($row['E']=="FEMME"){
                    $personal->setGenre('FEMME');
                }else{
                    $personal->setGenre('N/A');
                }


                $personal->setBirthday(new \DateTime($row['H'] ?? 'now')); // Date de naissance
                // NumCNPS

                $personal->setRefCNPS($row['J'] ?? $numCNPS);

                $personal->setLieuNaissance($row['I' ?? 'N/A']); //Lieu de naissance
                $personal->setPiece("CNI");
                $personal->setRefPiece('N/A');
                $personal->setTelephone($row['Q' ?? 'N/A']); // Phone number
                $personal->setEmail('N/A');

                // Category
                $categories = $this->categoryRepository->findAll();
                foreach($categories as $category){
                    if($category->getIntitule()==$row['S']){
                        $personal->setCategorie($category);
                    }
                }
                $personal->setConjoint('N/A');
                $personal->setNumCertificat('N/A');
                $personal->setNumExtraitActe('N/A');
                $personal->setEtatCivil($row['F'] ?? 'N/A'); // Situation matrimoniale
                $personal->setNiveauFormation('N/A');
                $personal->setFonction($row['P'] ?? 'N/A');
                $personal->setService($row['T'] ?? 'N/A');

                // Contract
                $contract->setRefContract($numContract);

                $contract->setDateEmbauche(new \DateTime($row['D'] ?? 'now')); // date d'embauche
                $contract->setDateEffet(new \DateTime($row['D'] ?? 'now'));
                $contract->setTypeContrat('CDI');
                $personal->setContract($contract);

                // Salaire
                $salary->setSursalaire($row['V'] ?? 0);
                $salary->setSmig(75000);
                $salary->setAmountAventage(0);
                $salary->setBrutImposable(0);
                $salary->setBaseAmount(0);
                $salary->setBrutAmount(0);
                $salary->setPrimeTransport(30000);
                $salary->setPersonal($personal);


                 // primes

                 $primes = $this->primesRepository->findAll();
                 foreach($primes as $prime){
 
                     if($row['W'] && $prime->getCode() == "PRIME TENUE TRAVAIL"){
                        $detailSalary = new DetailSalary();
                        $detailSalary->setAmountPrime($row['W']);
                        $detailSalary->setPrime($prime);
                        $salary->addDetailSalary($detailSalary);
                        
                        $this->entityManager->persist($detailSalary);
                     }
 
                     if($row['X'] && $prime->getCode() == "PRIME SALISSURE"){

                        $detailSalary = new DetailSalary();
                        $detailSalary->setAmountPrime($row['X']);   
                        $detailSalary->setPrime($prime);
                        $salary->addDetailSalary($detailSalary);

                        $this->entityManager->persist($detailSalary);
                     }
                 }
               // personne à charge

                for($i= 0; $i <$row['D']; $i++)
                {
                    $chargePeople =  new ChargePeople();
                    $chargePeople->setFirstName($row['B'] ?? 'N/A');
                    $chargePeople->setLastName($row['B'] ?? 'N/A');
                    $chargePeople->setBirthday(new \DateTime());
                    $chargePeople->setGender("HOMME");
                    $chargePeople->setNumPiece($matricule);
                    $chargePeople->setContact($row['Q'] ?? 'N/A');
                    $chargePeople->setPersonal($personal);
                    $this->entityManager->persist($chargePeople);
                }

               



                // Info bancaire
                $accountBank->setBankId(0);
                // Code
                $accountBank->setCode($row['L'] ?? 'N/A');
                // Code agence
                $accountBank->setCodeAgence($row['M'] ?? 'N/A');
                // Num compte
                $accountBank->setNumCompte($row['N'] ?? 'N/A');

                // cle rib
                $accountBank->setRib($row['O'] ?? 'N/A');

                // Nom bank
                $accountBank->setName($row['K'] ?? 'N/A');

                if($row['M']){

                    $personal->setModePaiement("VIREMENT");

                }else{

                    $personal->setModePaiement("CAISSE");
                }

                $accountBank->setPersonal($personal);
                $this->entityManager->persist($salary);
                $this->entityManager->persist($accountBank);
                $this->entityManager->persist($personal);
                $this->entityManager->flush();


            }
        return $this->success = true;

        } catch (\Exception $ex) {

            dd($ex->getMessage());
            return $this->success = false;
        }
    }
}