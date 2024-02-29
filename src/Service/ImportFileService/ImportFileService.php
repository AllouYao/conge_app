<?php

namespace App\Service\ImportFileService;

use App\Entity\DossierPersonal\DetailRetenueForfetaire;
use App\Service\MatriculeGenerator;
use App\Entity\DossierPersonal\Salary;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Entity\DossierPersonal\Contract;
use App\Entity\DossierPersonal\Personal;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Entity\DossierPersonal\AccountBank;
use App\Entity\DossierPersonal\ChargePeople;
use App\Repository\Settings\PrimesRepository;
use App\Repository\Settings\CategoryRepository;
use App\Entity\DossierPersonal\DetailSalary;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use Psr\Log\LoggerInterface;

class ImportFileService
{
    public bool $success = false;
    public function __construct(
        private MatriculeGenerator $matriculeGenerator,
        private CategoryRepository $categoryRepository,
        private PrimesRepository $primesRepository,
        private RetenueForfetaireRepository $retenueForfetaireRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $loggerInterface,
        )
        {

    }
    public function import($filePath):bool
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $reader = new Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            foreach ($worksheet as $row) {

                $amountBrut =0;
                $baseSalary =0;
                $amountPrimes = 0;
                $amountBrutImposable = 0;
                $surSursalaire =0;
                $transport = 30000.00;
                
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
                        $salary->setBaseAmount($category->getAmount());
                        $baseSalary += $category->getAmount();
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

                

                // baseAmount ---category
                // 


                //Assurance

                if($row['Z'] && !is_null($row['Z'])) {
                    $codeType = $row['Z'] === 'FAMILLE' ? 'ASSURANCE_SANTE_FAMILLE_SALARIALE' :'ASSURANCE_SANTE_CLASSIQUE_SALARIALE';
                    $retenue =$this->retenueForfetaireRepository->findOneBy(['code' =>$codeType]);
                    $detailRetenueForfetaire =  new DetailRetenueForfetaire();
                    $detailRetenueForfetaire->setSalary($salary);
                    $detailRetenueForfetaire->setRetenuForfetaire($retenue);
                    if($row['Z'] === 'FAMILLE') {
                        $amountEmpl = 3000.00;
                    }
                    if ($row['Z'] == 'CLASSIQUE' ) {
                        $amountEmpl = 1250.00;
                    }
                    $detailRetenueForfetaire->setAmount($row['Y']);
                    $detailRetenueForfetaire->setAmountEmp($amountEmpl);
                    $this->entityManager->persist($detailRetenueForfetaire);
                    $this->loggerInterface->info('Assurance traité');
                }


                 // primes
                if(!empty($row['W']) || !empty($row['X'])) {
                    $detailSalary = (new DetailSalary() )->setSalary($salary);
                    $primeTenue = 0;
                    $primeSalissure = 0;

                    if($row['W']) {
                        /** Fecth prime TENUE TRAVAIM */
                        $primeWork =  $this->primesRepository->findOneBy(array('code'=> 'PRIME TENUE TRAVAIL'));
                        $detailSalary->setAmountPrime($row['W']);
                        $detailSalary->setPrime($primeWork);
                        $primeTenue = $row['W'];
                    }

                    if  ($row['X']) {
                         /** Fecth prime TENUE TRAVAIM */
                        $primeSalissure =  $this->primesRepository->findOneBy(array('code'=> 'PRIME SALISSURE'));
                        $detailSalary->setAmountPrime($row['X']);  
                        $detailSalary->setPrime($primeSalissure);
                        $primeSalissure = $row['X'];
                    }
                    $amountPrimes = $primeTenue+$primeSalissure;
                    $this->entityManager->persist($detailSalary);
                    $this->loggerInterface->info('Assurance prime');
                }
                 

               // personne à charge
                
                for($i= 0; $i <$row['G']; $i++)
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
                    $this->loggerInterface->info('Charge pepole traité');
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

                // Total des primes juridiques

                //SurSalaire
                $surSursalaire = $row['V'] ?? 0;
                
                // Salaire brut
                $amountBrut = $baseSalary + $surSursalaire + $amountPrimes + $transport;

                //Salaire brut imposable
                $amountBrutImposable = $baseSalary + $surSursalaire + $amountPrimes;

                // Salaire
                $salary->setSursalaire($surSursalaire);
                $salary->setSmig(75000.0);
                $salary->setAmountAventage(0);
                $salary->setBrutImposable($amountBrutImposable);
                $salary->setBrutAmount($amountBrut);
                $salary->setPrimeTransport($transport);
                // $salary->setPersonal($personal);
                $salary->setTotalPrimeJuridique($amountPrimes);
                $salary->setAvantage(null);

                $personal->setSalary($salary);
                $accountBank->setPersonal($personal);
                $this->entityManager->persist($salary);
                $this->entityManager->persist($accountBank);
                $this->entityManager->persist($personal);
                $this->entityManager->flush();


            }
        return $this->success = true;

        } catch (\Exception $ex) {

            dd($ex->getMessage(), $row['A']);
            return $this->success = false;
        }
    }
}