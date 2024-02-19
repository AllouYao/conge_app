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
        UtimePaiementService      $utimePaiementService,
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
        $primeTransportLegal = round($this->utimePaiementService->getPrimeTransportLegal(), 2);


        /** Récupération des éléménts de salaire imposable */
        $salary = $personal->getSalary();
        $salaire = $this->utimePaiementService->getAmountSalaireBrutAndImposable($personal);
        $baseSalaire = round((double)$salaire['salaire_categoriel'], 2);
        $sursalaire = round((double)$salary->getSursalaire(), 2);
        $majorationHeursSupp = round($this->utimePaiementService->getAmountMajorationHeureSupp($personal), 2);
        $primeAnciennete = round($this->utimePaiementService->getAmountAnciennete($personal), 2);
        /** enregistrer la prime d'ancienneté dans la table salary */
        $salary->setPrimeAciennete((int)$primeAnciennete);
        $congesPayes = round($this->utimePaiementService->getAmountCongesPayes($personal), 2);
        $primeFonctions = round($this->utimePaiementService->getPrimeFonction($personal), 2);
        $primeLogements = round($this->utimePaiementService->getPrimeLogement($personal), 2);
        $indemniteFonctions = round($this->utimePaiementService->getIndemniteFonction($personal), 2);
        $indemniteLogements = round($this->utimePaiementService->getIndemniteLogement($personal), 2);
        $primeTransportImposable = round(((double)$salary->getPrimeTransport() - $primeTransportLegal), 2);

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage(), 2);
        $avantageNatureImposable = round(((double)$salary?->getAmountAventage() - $avantageNonImposable), 2);

        /** charge du personnel et retenue fixcal */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = round($chargePersonal?->getNumPart(), 2);
        $salaryIts = round($chargePersonal?->getAmountIts(), 2);
        $salaryCnps = round($chargePersonal?->getAmountCNPS(), 2);
        $salaryCmu = round($chargePersonal?->getAmountCMU(), 2);
        $assuranceSanteSalariale = $this->utimePaiementService->getAssuranceSante($personal)['assurance_salariale'];
        $chargeSalarie = round($chargePersonal?->getAmountTotalChargePersonal() + $assuranceSanteSalariale, 2);

        /** charge de l'employeur et retenue fixcal */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal]);
        $employeurIS = round($chargeEmployeur?->getAmountIS(), 2);
        $employeurFPC = round($chargeEmployeur?->getAmountFPC(), 2);
        $employeurFPCAnnuel = round($chargeEmployeur?->getAmountAnnuelFPC(), 2);
        $employeurTA = round($chargeEmployeur?->getAmountTA(), 2);
        $employeurCMU = round($chargeEmployeur?->getAmountCMU(), 2);
        $employeurCR = round($chargeEmployeur?->getAmountCR(), 2);
        $employeurPF = round($chargeEmployeur?->getAmountPF(), 2);
        $employeurAT = round($chargeEmployeur?->getAmountAT(), 2);
        $employeurCNPS = round($chargeEmployeur?->getTotalRetenuCNPS(), 2);
        $assuranceSantePatronale = $this->utimePaiementService->getAssuranceSante($personal)['assurance_patronale'];
        $chargePatronal = round($chargeEmployeur?->getTotalChargeEmployeur() + $assuranceSantePatronale, 2);

        /** Récupération des éléménts de salaire non imposable */
        $primePaniers = round($this->utimePaiementService->getPrimePanier($personal), 2);
        $primeSalissures = round($this->utimePaiementService->getPrimeSalissure($personal), 2);
        $primeTenueTravails = round($this->utimePaiementService->getPrimeTT($personal), 2);
        $primeOutillages = round($this->utimePaiementService->getPrimeOutil($personal), 2);
        $primeRendement = round($this->utimePaiementService->getPrimeRendement($personal), 2);

        /** Salaire brut et le net imposable */
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements
            + $primeTransportImposable + $avantageNatureImposable + $primeTransportLegal + $avantageNonImposable;

        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements + $primeTransportImposable
            + $avantageNatureImposable;
        /** net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = $netImposable + $primeTransportLegal + $avantageNonImposable + $primeRendement + $primeOutillages
            + $primePaniers + $primeSalissures + $primeTenueTravails - $chargeSalarie;

        /** la masse salariale */
        $masseSalaried = $netPayer + $chargePatronal;

        /** Enregistrement du livre de paie */
        $payroll = (new Payroll())
            ->setPersonal($personal)
            ->setCampagne($campagne)
            ->setDateCreated($campagne->getStartedAt())
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
            ->setSalarySante($assuranceSanteSalariale)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurIs($employeurIS)
            ->setEmployeurCmu($employeurCMU)
            ->setEmployeurCnps($employeurCNPS)
            ->setEmployeurCr($employeurCR)
            ->setAmountTA($employeurTA)
            ->setAmountFPC($employeurFPC)
            ->setAmountAnnuelFPC($employeurFPCAnnuel)
            ->setEmployeurSante($assuranceSantePatronale)
            ->setFixcalAmount($salaryIts)
            ->setSocialAmount($salaryCnps + $salaryCmu)
            ->setTotalRetenueSalarie($chargeSalarie)
            ->setFixcalAmountEmployeur($employeurTA + $employeurFPC + $employeurFPCAnnuel + $employeurIS)
            ->setSocialAmountEmployeur($employeurPF + $employeurAT + $employeurCMU + $employeurCNPS)
            ->setTotalRetenuePatronal($chargePatronal)
            ->setAmountPrimePanier($primePaniers)
            ->setAmountPrimeSalissure($primeSalissures)
            ->setAmountPrimeOutillage($primeOutillages)
            ->setAmountPrimeTenueTrav($primeTenueTravails)
            ->setAmountPrimeRendement($primeRendement)
            ->setNetPayer($netPayer)
            ->setMasseSalary($masseSalaried);

        $this->manager->persist($payroll);
        $this->manager->persist($salary);
    }

    public function setPayrollOfDeparture(Personal $personal, Campagne $campagne): void
    {
        /** Récupération des élements personnel du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') -' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();

        /** charge du personnel et retenue fixcal */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal, 'departure' => $personal->getDepartures()]);
        $nombrePart = round($chargePersonal?->getNumPart(), 2);
        $salaryIts = round($chargePersonal?->getAmountIts(), 2);
        $salaryCnps = round($chargePersonal?->getAmountCNPS(), 2);
        $salaryCmu = round($chargePersonal?->getAmountCMU(), 2);
        $chargeSalarie = round($chargePersonal?->getAmountTotalChargePersonal(), 2);

        /** charge de l'employeur et retenue fixcal */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal, 'departure' => $personal->getDepartures()]);
        $employeurIS = round($chargeEmployeur?->getAmountIS(), 2);
        $employeurFPC = round($chargeEmployeur?->getAmountFPC(), 2);
        $employeurFPCAnnuel = round($chargeEmployeur?->getAmountAnnuelFPC(), 2);
        $employeurTA = round($chargeEmployeur?->getAmountTA(), 2);
        $employeurCMU = round($chargeEmployeur?->getAmountCMU(), 2);
        $employeurCR = round($chargeEmployeur?->getAmountCR(), 2);
        $employeurPF = round($chargeEmployeur?->getAmountPF(), 2);
        $employeurAT = round($chargeEmployeur?->getAmountAT(), 2);
        $employeurCNPS = round($chargeEmployeur?->getTotalRetenuCNPS(), 2);
        $chargePatronal = round($chargeEmployeur?->getTotalChargeEmployeur(), 2);

        /** Récupération des éléménts de depart */
        $departures = $personal->getDepartures();
        $gratificationProrata = $departures?->getGratification();
        $indemniteConges = $departures?->getCongeAmount();
        $indemnitePreavis = $departures?->getNoticeAmount();
        $indemniteLicenciement = $departures?->getDissmissalAmount();
        $amountLicenciementImposable = $departures?->getAmountLcmtImposable();
        $amountLicenciementNoImposable = $departures?->getAmountLcmtNoImposable();
        $totalBrutImposable = $departures?->getTotalIndemniteImposable();

        /** Total droits et indemnite */
        $totalBrut = $gratificationProrata + $indemniteConges + $indemnitePreavis + $indemniteLicenciement;

        /** net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = round($totalBrutImposable - $chargeSalarie, 2);

        /** Enregistrement du livre de paie */
        $payroll = (new Payroll())
            ->setPersonal($personal)
            ->setCampagne($campagne)
            ->setDateCreated($campagne->getStartedAt())
            ->setMatricule($matricule)
            ->setService($service)
            ->setCategories($categorie)
            ->setNumberPart($nombrePart)
            ->setDateEmbauche($dateEmbauche)
            ->setNumCnps($numeroCnps)
            ->setDepartement($departement)
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
            ->setGratificationD($gratificationProrata)
            ->setAllocationCongeD($indemniteConges)
            ->setPreavisAmount($indemnitePreavis)
            ->setLicemciementImposable($amountLicenciementImposable)
            ->setLicenciementNoImpo($amountLicenciementNoImposable)
            ->setTotalIndemniteBrut($totalBrut)
            ->setTotalIndemniteImposable($totalBrutImposable)
            ->setNetPayer($netPayer);

        $this->manager->persist($payroll);
    }
}