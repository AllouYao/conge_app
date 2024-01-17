<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayrollService
{
    private EntityManagerInterface $manager;
    private ChargeEmployeurRepository $chargeEmployeurRepository;
    private ChargePersonalsRepository $chargePersonalsRepository;
    private UtimePaiementService $utimePaiementService;


    public function __construct(
        EntityManagerInterface    $entityManager,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        ChargePersonalsRepository $chargePersonalsRepository,
        UtimePaiementService      $utimePaiementService
    )
    {
        $this->manager = $entityManager;
        $this->chargeEmployeurRepository = $chargeEmployeurRepository;
        $this->chargePersonalsRepository = $chargePersonalsRepository;
        $this->utimePaiementService = $utimePaiementService;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     */
    public function setPayroll(Personal $personal, Campagne $campagne): void
    {
        /** Récupération des élements personnel du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') -' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();

        /** Prime de transport non imposable */
        $primeTransportLegal = $this->utimePaiementService->getPrimeTransportLegal();


        /** Récupération des éléménts de salaire imposable */
        $salary = $personal->getSalary();
        $salaire = $this->utimePaiementService->getAmountSalaireBrutAndImposable($personal);
        $baseSalaire = (int)$salaire['salaire_categoriel'];
        $sursalaire = $salary->getSursalaire();
        $majorationHeursSupp = $this->utimePaiementService->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->utimePaiementService->getAmountAnciennete($personal);
        $salary->setPrimeAciennete((int)$primeAnciennete);
        $congesPayes = $this->utimePaiementService->getAmountCongesPayes($personal);
        $primeFonctions = $this->utimePaiementService->getPrimeFonction($personal);
        $primeLogements = $this->utimePaiementService->getPrimeLogement($personal);
        $indemniteFonctions = $this->utimePaiementService->getIndemniteFonction($personal);
        $indemniteLogements = $this->utimePaiementService->getIndemniteLogement($personal);
        $primeTransportImposable = (int)$salary->getPrimeTransport() - $primeTransportLegal;

        /** Avantage en nature non imposable */
        $avantageNonImposable = (int)$salary->getAvantage()?->getTotalAvantage();
        $avantageNatureImposable = (int)$salary?->getAmountAventage() - $avantageNonImposable;


        /** charge du personnel et retenue fixcal */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = $chargePersonal?->getNumPart();
        $salaryIts = $chargePersonal?->getAmountIts();
        $salaryCnps = $chargePersonal?->getAmountCNPS();
        $salaryCmu = $chargePersonal?->getAmountCMU();
        $chargeSalarie = $chargePersonal?->getAmountTotalChargePersonal();

        /** charge de l'employeur et retenue fixcal */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal]);
        $employeurIS = $chargeEmployeur?->getAmountIS();
        $employeurFPC = $chargeEmployeur?->getAmountFPC();
        $employeurFPCAnnuel = $chargeEmployeur?->getAmountAnnuelFPC();
        $employeurTA = $chargeEmployeur?->getAmountTA();
        $employeurCMU = $chargeEmployeur?->getAmountCMU();
        $employeurCR = $chargeEmployeur?->getAmountCR();
        $employeurPF = $chargeEmployeur?->getAmountPF();
        $employeurAT = $chargeEmployeur?->getAmountAT();
        $employeurCNPS = $chargeEmployeur?->getTotalRetenuCNPS();
        $chargePatronal = $chargeEmployeur?->getTotalChargeEmployeur();

        /** Récupération des éléménts de salaire non imposable */
        $primePaniers = $this->utimePaiementService->getPrimePanier($personal);
        $primeSalissures = $this->utimePaiementService->getPrimeSalissure($personal);
        $primeTenueTravails = $this->utimePaiementService->getPrimeTT($personal);
        $primeOutillages = $this->utimePaiementService->getPrimeOutil($personal);
        $primeRendement = $this->utimePaiementService->getPrimeRendement($personal);

        /** Salaire brut et le net imposable */
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements
            + $primeTransportImposable + $avantageNatureImposable + $primeTransportLegal + $avantageNonImposable;

        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements + $primeTransportImposable
            + $avantageNatureImposable;

        /** net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = $salaireBrut + $primeRendement + $primeOutillages + $primePaniers + $primeSalissures + $primeTenueTravails
            - $chargeSalarie;

        /** la masse salariale */


        /** Enregistrement du livre de paie */
        $payroll = (new Payroll())
            ->setPersonal($personal)
            ->setCampagne($campagne)
            ->setMatricule($matricule)
            ->setService($service)
            ->setCategories($categorie)
            ->setNumberPart($nombrePart)
            ->setDateEmbauche($dateEmbauche)
            ->setNumCnps($numeroCnps)
            ->setDepartement($departement)
            ->setBaseAmount($baseSalaire)
            ->setSursalaire($sursalaire)
            ->setMajorationAmount($majorationHeursSupp)
            ->setAncienneteAmount($primeAnciennete)
            ->setCongesPayesAmount($congesPayes)
            ->setPrimeFonctionAmount($primeFonctions)
            ->setPrimeLogementAmount($primeLogements)
            ->setIndemniteFonctionAmount($indemniteFonctions)
            ->setIndemniteLogementAmount($indemniteLogements)
            ->setAmountTransImposable($primeTransportImposable)
            ->setAmountAvantageImposable($avantageNatureImposable)
            ->setSalaryTransport($primeTransportLegal)
            ->setAventageNonImposable($avantageNonImposable)
            ->setBrutAmount($salaireBrut)
            ->setImposableAmount($netImposable)
            ->setSalaryIts($salaryIts)
            ->setSalaryCnps($salaryCnps)
            ->setSalaryCmu($salaryCmu)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurIs($employeurIS)
            ->setEmployeurCmu($employeurCMU)
            ->setEmployeurCnps($employeurCNPS)
            ->setEmployeurCr($employeurCR)
            ->setAmountTA($employeurTA)
            ->setAmountFPC($employeurFPC)
            ->setAmountAnnuelFPC($employeurFPCAnnuel)
            ->setFixcalAmount($chargeSalarie)
            ->setFixcalAmountEmployeur($chargePatronal)
            ->setAmountPrimePanier($primePaniers)
            ->setAmountPrimeSalissure($primeSalissures)
            ->setAmountPrimeOutillage($primeOutillages)
            ->setAmountPrimeTenueTrav($primeTenueTravails)
            ->setAmountPrimeRendement($primeRendement)
            ->setNetPayer($netPayer);


        $this->manager->persist($payroll);
        $this->manager->persist($salary);
    }
}