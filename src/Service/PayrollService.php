<?php

namespace App\Service;

use App\Entity\DossierPersonal\Departure;
use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Service\CasExeptionel\DepartureCampagneService;
use App\Service\PaieService\PaieServices;
use App\Service\Personal\PrimeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PayrollService
{
    private EntityManagerInterface $manager;
    private ChargeEmployeurRepository $chargeEmployeurRepository;
    private ChargePersonalsRepository $chargePersonalsRepository;
    private PrimeService $primeService;
    private PaieServices $paieServices;
    private DepartureCampagneService $departureCampagneService;


    public function __construct(
        EntityManagerInterface    $entityManager,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        ChargePersonalsRepository $chargePersonalsRepository,
        PrimeService              $primeService,
        PaieServices              $paieServices,
        DepartureCampagneService  $departureCampagneService
    )
    {
        $this->manager = $entityManager;
        $this->chargeEmployeurRepository = $chargeEmployeurRepository;
        $this->chargePersonalsRepository = $chargePersonalsRepository;
        $this->primeService = $primeService;
        $this->paieServices = $paieServices;
        $this->departureCampagneService = $departureCampagneService;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     * @throws Exception
     */
    public function setPayroll(Personal $personal, Campagne $campagne): void
    {

        /** Nombre de jour travailler pendant la periode current de paie */
        $dayOfCurrentMonth = $this->paieServices->NbDayOfPresenceByCurrentMonth($campagne);

        /** Ajouter les Informations utile du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') -' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();


        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $personal->getSalary();
        $salaire = $this->paieServices->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $baseSalaire = round((double)$salaire['salaire_categoriel']);
        $sursalaire = round((double)$salary->getSursalaire());
        $majorationHeursSupp = round($this->paieServices->getHeureSuppCampagne($personal, $campagne));
        $primeAnciennete = round($this->paieServices->getPrimeAncienneteCampagne($personal));
        $salary->setPrimeAciennete((int)$primeAnciennete); // Enregistrer après récuperation la prime d'anciennete dans la table salary
        $congesPayes = null; // Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation en fonction du mois de campagne.


        /** Ajouter toutes les primes possible  */
        $primeFonctions = round($this->primeService->getPrimeFonction($personal));
        $primeLogements = round($this->primeService->getPrimeLogement($personal));
        $indemniteFonctions = round($this->primeService->getIndemniteFonction($personal));
        $indemniteLogements = round($this->primeService->getIndemniteLogement($personal));
        $primeTransportLegal = round($this->primeService->getPrimeTransportLegal());
        $primeTransportImposable = round(((double)$salary->getPrimeTransport() - $primeTransportLegal));
        $primePaniers = round($this->primeService->getPrimePanier($personal));
        $primeSalissures = round($this->primeService->getPrimeSalissure($personal));
        $primeTenueTravails = round($this->primeService->getPrimeTT($personal));
        $primeOutillages = round($this->primeService->getPrimeOutil($personal));
        $primeRendement = round($this->primeService->getPrimeRendement($personal));

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage());
        $avantageNatureImposable = round(((double)$salary?->getAmountAventage() - $avantageNonImposable));


        /** Ajouter les charges du salarié ( retenues fiscales et sociales) */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = round($chargePersonal?->getNumPart(), 1);
        $salaryIts = round($chargePersonal?->getAmountIts());
        $salaryCnps = round($chargePersonal?->getAmountCNPS());
        $salaryCmu = round($chargePersonal?->getAmountCMU());
        $assuranceSanteSalariale = $this->paieServices->amountAssuranceSante($personal)['assurance_salariale'];
        $amountChargFiscalPersonal = $salaryIts;
        $amountChargSocialPersonal = $salaryCnps + $salaryCmu + $assuranceSanteSalariale;
        $chargeSalarie = round($amountChargFiscalPersonal + $amountChargSocialPersonal);


        /** Ajouter les charges de l'employeur (retenues fiscales et sociales) */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal]);
        $employeurIS = round($chargeEmployeur?->getAmountIS());
        $employeurFPC = round($chargeEmployeur?->getAmountFPC());
        $employeurFPCAnnuel = round($chargeEmployeur?->getAmountAnnuelFPC());
        $employeurTA = round($chargeEmployeur?->getAmountTA());
        $employeurCMU = round($chargeEmployeur?->getAmountCMU());
        $employeurCR = round($chargeEmployeur?->getAmountCR());
        $employeurPF = round($chargeEmployeur?->getAmountPF());
        $employeurAT = round($chargeEmployeur?->getAmountAT());
        $assuranceSantePatronale = $this->paieServices->amountAssuranceSante($personal)['assurance_patronale'];
        $amountChargFiscalPatronale = $employeurIS + $employeurFPC + $employeurFPCAnnuel + $employeurTA;
        $amountChargSocialPatronale = $employeurCMU + $employeurCR + $employeurAT + $employeurPF + $assuranceSantePatronale;
        $chargePatronal = round($amountChargFiscalPatronale + $amountChargSocialPatronale);


        /** Ajouter le salaire brut qui constitue l'ensemble des élements de salaire imposable et non imposable */
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements
            + $primeTransportImposable + $avantageNatureImposable + $primeTransportLegal + $avantageNonImposable
            + $primeRendement + $primeOutillages + $primePaniers + $primeSalissures + $primeTenueTravails;


        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements + $primeTransportImposable
            + $avantageNatureImposable + $primeRendement + $primeOutillages
            + $primePaniers + $primeSalissures + $primeTenueTravails;


        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = round($netImposable + $primeTransportLegal + $avantageNonImposable - $chargeSalarie);

        /** Ajouter la masse salariale */
        $masseSalaried = $netPayer + $chargePatronal + $chargeSalarie;
        $payroll = (new Payroll())
            ->setDayOfPresence($dayOfCurrentMonth)
            /** Info personal et campagne */
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
            /** Element de salaire */
            ->setBaseAmount($baseSalaire)
            ->setSursalaire($sursalaire)
            ->setMajorationAmount($majorationHeursSupp)
            ->setAncienneteAmount($primeAnciennete)
            ->setCongesPayesAmount($congesPayes)
            /** Toutes prime */
            ->setPrimeFonctionAmount($primeFonctions)
            ->setPrimeLogementAmount($primeLogements)
            ->setIndemniteFonctionAmount($indemniteFonctions)
            ->setIndemniteLogementAmount($indemniteLogements)
            ->setAmountTransImposable($primeTransportImposable)
            ->setAmountAvantageImposable($avantageNatureImposable)
            ->setSalaryTransport($primeTransportLegal)
            ->setAventageNonImposable($avantageNonImposable)
            ->setAmountPrimePanier($primePaniers)
            ->setAmountPrimeSalissure($primeSalissures)
            ->setAmountPrimeOutillage($primeOutillages)
            ->setAmountPrimeTenueTrav($primeTenueTravails)
            ->setAmountPrimeRendement($primeRendement)
            /** Charge du personal sociale et fiscale */
            ->setSalaryIts($salaryIts)
            ->setSalaryCnps($salaryCnps)
            ->setSalaryCmu($salaryCmu)
            ->setSalarySante($assuranceSanteSalariale)
            ->setFixcalAmount($amountChargFiscalPersonal)
            ->setSocialAmount($amountChargSocialPersonal)
            ->setTotalRetenueSalarie($chargeSalarie)
            /** Charge patronal sociale et fiscale */
            ->setEmployeurIs($employeurIS)
            ->setAmountTA($employeurTA)
            ->setAmountFPC($employeurFPC)
            ->setAmountAnnuelFPC($employeurFPCAnnuel)
            ->setEmployeurFdfp($employeurTA + $employeurFPC + $employeurFPCAnnuel)
            ->setEmployeurCr($employeurCR)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurCmu($employeurCMU)
            ->setEmployeurCnps($employeurCR + $employeurPF + $employeurAT)
            ->setEmployeurSante($assuranceSantePatronale)
            ->setFixcalAmountEmployeur($amountChargFiscalPatronale)
            ->setSocialAmountEmployeur($amountChargSocialPatronale)
            ->setTotalRetenuePatronal($chargePatronal)
            /** Brut et Net imposable du salarié */
            ->setBrutAmount($salaireBrut)
            ->setImposableAmount($netImposable)
            /** Net à payer et masse salariale du salarié */
            ->setNetPayer($netPayer)
            ->setMasseSalary($masseSalaried);

        /** Enregistrement du livre de paie */
        $this->manager->persist($payroll);
        $this->manager->persist($salary);
    }

    /**
     * @throws Exception
     */
    public function setExeptionnelPayroll(Departure $departure, Campagne $campagne): void
    {
        /** Nombre de jour travailler pendant la periode current de paie */
        $dayOfPresence = $this->departureCampagneService->NbDayOfPresenceBeforeDeparture($departure);
        $dayOfCurrentMonth = $this->departureCampagneService->NbDayOfPresenceByCurrentMonth($departure);

        /** Ajouter les Informations utile du salarié */
        $personal = $departure->getPersonal();
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') -' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();


        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $personal->getSalary();
        $baseSalaire = round($this->departureCampagneService->baseAmountByNbDayOfPresence($departure));
        $sursalaire = round((double)$salary->getSursalaire() * ($dayOfPresence / $dayOfCurrentMonth));
        $majorationHeursSupp = round($this->departureCampagneService->amountHeureSuppByCampagneAndDeparture($departure, $campagne));
        $primeAnciennete = round($this->departureCampagneService->amountPrimeAncienneteCampagneByDeparture($departure));
        $salary->setPrimeAciennete((int)$primeAnciennete); // Enregistrer après récuperation la prime d'anciennete dans la table salary.
        $congesPayes = null; // Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation en fonction du mois de campagne.


        /** Ajouter toutes les primes possible  */
        $primeFonctions = round($this->primeService->getPrimeFonction($personal));
        $primeLogements = round($this->primeService->getPrimeLogement($personal));
        $indemniteFonctions = round($this->primeService->getIndemniteFonction($personal));
        $indemniteLogements = round($this->primeService->getIndemniteLogement($personal));
        $primeTransportLegal = round($this->primeService->getPrimeTransportLegal() * ($dayOfPresence / $dayOfCurrentMonth));
        $primeTransportImposable = round(((double)$salary->getPrimeTransport() - $primeTransportLegal));
        $primePaniers = round($this->primeService->getPrimePanier($personal));
        $primeSalissures = round($this->primeService->getPrimeSalissure($personal));
        $primeTenueTravails = round($this->primeService->getPrimeTT($personal));
        $primeOutillages = round($this->primeService->getPrimeOutil($personal));
        $primeRendement = round($this->primeService->getPrimeRendement($personal));

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage());
        $avantageNatureImposable = round(((double)$salary?->getAmountAventage() - $avantageNonImposable));


        /** Ajouter les charges du salarié ( retenues fiscales et sociales) */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = round($chargePersonal?->getNumPart(), 1);
        $salaryIts = round($chargePersonal?->getAmountIts());
        $salaryCnps = round($chargePersonal?->getAmountCNPS());
        $salaryCmu = round($chargePersonal?->getAmountCMU());
        $assuranceSanteSalariale = $this->departureCampagneService->amountAssuranceSanteByDeparture($personal->getDepartures())['assurance_salariale'];
        $amountChargFiscalPersonal = $salaryIts;
        $amountChargSocialPersonal = $salaryCnps + $salaryCmu + $assuranceSanteSalariale;
        $chargeSalarie = round($amountChargFiscalPersonal + $amountChargSocialPersonal);


        /** Ajouter les charges de l'employeur (retenues fiscales et sociales) */
        $chargeEmployeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $personal]);
        $employeurIS = round($chargeEmployeur?->getAmountIS());
        $employeurFPC = round($chargeEmployeur?->getAmountFPC());
        $employeurFPCAnnuel = round($chargeEmployeur?->getAmountAnnuelFPC());
        $employeurTA = round($chargeEmployeur?->getAmountTA());
        $employeurCMU = round($chargeEmployeur?->getAmountCMU());
        $employeurCR = round($chargeEmployeur?->getAmountCR());
        $employeurPF = round($chargeEmployeur?->getAmountPF());
        $employeurAT = round($chargeEmployeur?->getAmountAT());
        $assuranceSantePatronale = $this->departureCampagneService->amountAssuranceSanteByDeparture($personal->getDepartures())['assurance_patronale'];
        $amountChargFiscalPatronale = $employeurIS + $employeurFPC + $employeurFPCAnnuel + $employeurTA;
        $amountChargSocialPatronale = $employeurCMU + $employeurCR + $employeurAT + $employeurPF + $assuranceSantePatronale;
        $chargePatronal = round($amountChargFiscalPatronale + $amountChargSocialPatronale);


        $brutImposable = $this->departureCampagneService->netImposableByNbDayOfPresence($departure);
        /** Ajouter le salaire brut qui constitue l'ensemble des élements de salaire imposable et non imposable */
        $salaireBrut = $brutImposable + $majorationHeursSupp + $congesPayes + $primeAnciennete + $primeTransportLegal;


        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $netImposable = $brutImposable + $majorationHeursSupp + $congesPayes + $primeAnciennete;


        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = round($netImposable + $primeTransportLegal + $avantageNonImposable - $chargeSalarie);


        /** Ajouter la masse salariale */
        $masseSalaried = $netPayer + $chargePatronal + $chargeSalarie;
        $payroll = (new Payroll())
            ->setDayOfPresence($dayOfPresence)
            /** Info personal et campagne */
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
            /** Element de salaire */
            ->setBaseAmount($baseSalaire)
            ->setSursalaire($sursalaire)
            ->setMajorationAmount($majorationHeursSupp)
            ->setAncienneteAmount($primeAnciennete)
            ->setCongesPayesAmount($congesPayes)
            /** Toutes prime */
            ->setPrimeFonctionAmount($primeFonctions)
            ->setPrimeLogementAmount($primeLogements)
            ->setIndemniteFonctionAmount($indemniteFonctions)
            ->setIndemniteLogementAmount($indemniteLogements)
            ->setAmountTransImposable($primeTransportImposable)
            ->setAmountAvantageImposable($avantageNatureImposable)
            ->setSalaryTransport($primeTransportLegal)
            ->setAventageNonImposable($avantageNonImposable)
            ->setAmountPrimePanier($primePaniers)
            ->setAmountPrimeSalissure($primeSalissures)
            ->setAmountPrimeOutillage($primeOutillages)
            ->setAmountPrimeTenueTrav($primeTenueTravails)
            ->setAmountPrimeRendement($primeRendement)
            /** Charge du personal sociale et fiscale */
            ->setSalaryIts($salaryIts)
            ->setSalaryCnps($salaryCnps)
            ->setSalaryCmu($salaryCmu)
            ->setSalarySante($assuranceSanteSalariale)
            ->setFixcalAmount($amountChargFiscalPersonal)
            ->setSocialAmount($amountChargSocialPersonal)
            ->setTotalRetenueSalarie($chargeSalarie)
            /** Charge patronal sociale et fiscale */
            ->setEmployeurIs($employeurIS)
            ->setAmountTA($employeurTA)
            ->setAmountFPC($employeurFPC)
            ->setAmountAnnuelFPC($employeurFPCAnnuel)
            ->setEmployeurFdfp($employeurTA + $employeurFPC + $employeurFPCAnnuel)
            ->setEmployeurCr($employeurCR)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurCmu($employeurCMU)
            ->setEmployeurCnps($employeurCR + $employeurPF + $employeurAT)
            ->setEmployeurSante($assuranceSantePatronale)
            ->setFixcalAmountEmployeur($amountChargFiscalPatronale)
            ->setSocialAmountEmployeur($amountChargSocialPatronale)
            ->setTotalRetenuePatronal($chargePatronal)
            /** Brut et Net imposable du salarié */
            ->setBrutAmount($salaireBrut)
            ->setImposableAmount($netImposable)
            /** Net à payer et masse salariale du salarié */
            ->setNetPayer($netPayer)
            ->setMasseSalary($masseSalaried);
        /** Enregistrement du livre de paie */
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