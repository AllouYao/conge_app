<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Entity\Paiement\Payroll;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Service\PaieService\PaieByPeriodService;
use App\Service\PaieService\PaieProrataService;
use App\Service\PaieService\PaieServices;
use App\Service\Personal\PrimeService;
use App\Utils\Status;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PayrollService
{
    const DAY_IN_MONTH_WORK = 30;
    private EntityManagerInterface $manager;
    private ChargeEmployeurRepository $chargeEmployeurRepository;
    private ChargePersonalsRepository $chargePersonalsRepository;
    private PrimeService $primeService;
    private PaieServices $paieServices;
    private PaieByPeriodService $paieByPeriodService;
    private PaieProrataService $paieProrataService;
    private CongeRepository $congeRepository;


    public function __construct(
        EntityManagerInterface    $entityManager,
        ChargeEmployeurRepository $chargeEmployeurRepository,
        ChargePersonalsRepository $chargePersonalsRepository,
        PrimeService              $primeService,
        PaieServices              $paieServices,
        PaieByPeriodService       $paieByPeriodService,
        PaieProrataService        $paieProrataService, CongeRepository $congeRepository
    )
    {
        $this->manager = $entityManager;
        $this->chargeEmployeurRepository = $chargeEmployeurRepository;
        $this->chargePersonalsRepository = $chargePersonalsRepository;
        $this->primeService = $primeService;
        $this->paieServices = $paieServices;
        $this->paieByPeriodService = $paieByPeriodService;
        $this->paieProrataService = $paieProrataService;
        $this->congeRepository = $congeRepository;
    }

    /**
     * @param Personal $personal
     * @param Campagne $campagne
     * @return Payroll
     * @throws Exception
     */
    public function setPayroll(Personal $personal, Campagne $campagne): Payroll
    {
        /** Nombre de jour travailler pendant la periode current de paie */
        $dayOfCurrentMonth = $this->paieServices->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne)['day_of_presence'];

        /** Ajouter les Informations utile du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') - ' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();


        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $personal->getSalary();
        $salaire = $this->paieServices->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $baseSalaire = ceil((double)$salaire['salaire_categoriel']);
        $sursalaire = ceil((double)$salary->getSursalaire() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $majorationHeursSupp = ceil($this->paieServices->getHeureSuppCampagne($personal, $campagne));
        $primeAnciennete = ceil($this->paieServices->getPrimeAncienneteCampagne($personal, $campagne));
        $salary->setPrimeAciennete((int)$primeAnciennete); // Enregistrer après récuperation la prime d'anciennete dans la table salary
        $congesPayes = $this->paieServices->getAmountCongesPayes($personal);// Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation en fonction du mois de campagne.

        /** Ajouter toutes les primes possible  */
        $primeFonctions = ceil($this->primeService->getPrimeFonction($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeLogements = ceil($this->primeService->getPrimeLogement($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $indemniteFonctions = ceil($this->primeService->getIndemniteFonction($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $indemniteLogements = ceil($this->primeService->getIndemniteLogement($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeTransportLegal = ceil($this->primeService->getPrimeTransportLegal());
        $primeTransportImposable = ($salary->getPrimeTransport() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) >= $primeTransportLegal
            ? ceil(((double)($salary->getPrimeTransport() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) - $primeTransportLegal)) : 0;
        $primePaniers = round($this->primeService->getPrimePanier($personal));
        $primeSalissures = round($this->primeService->getPrimeSalissure($personal));
        $primeTenueTravails = round($this->primeService->getPrimeTT($personal));
        $primeOutillages = round($this->primeService->getPrimeOutil($personal));
        $primeRendement = round($this->primeService->getPrimeRendement($personal));

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage());
        $avantageNatureImposable = ($salary?->getAmountAventage() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) >= $avantageNonImposable ?
            round(((double)($salary->getAmountAventage() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) - $avantageNonImposable)) : 0;


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


        /** Ajouter les régularisations sur brut imposable, remboursement ou retenue */
        $remboursementBrut = $this->paieServices->getRegulRemboursement($personal)['remboursement_brut'];
        $remboursementNet = $this->paieServices->getRegulRemboursement($personal)['remboursement_net'];
        $retenueBrut = $this->paieServices->getRegulRetenue($personal)['retenue_brut'];
        $retenueNet = $this->paieServices->getRegulRetenue($personal)['retenue_net'];

        /** Ajouter le salaire brut qui constitue l'ensemble des élements de salaire imposable et non imposable */
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements
            + $primeTransportImposable + $avantageNatureImposable + $primeTransportLegal + $avantageNonImposable;


        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp + $congesPayes + $primeAnciennete
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements + $primeTransportImposable
            + $avantageNatureImposable + $remboursementBrut - $retenueBrut;

        /** Ajouter les prêt et account qui constitue des retenues sur salaire net */
        $amountMensualityPret = $this->paieServices->amountPretCampagne($personal);
        $amountMensuelAcompt = $this->paieServices->amountAcomptCampagne($personal);


        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = ceil($netImposable + $primeTransportLegal + $avantageNonImposable + $remboursementNet + $primePaniers + $primeRendement + $primeOutillages + $primeSalissures + $primeTenueTravails - ($chargeSalarie + $retenueNet + $amountMensualityPret + $amountMensuelAcompt));

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
            ->setCongesPayesAmount($congesPayes)
            /** Regularisation sur net ou brut */
            ->setRemboursBrut($remboursementBrut)
            ->setRemboursNet($remboursementNet)
            ->setRetenueBrut($retenueBrut)
            ->setRetenueNet($retenueNet)
            /** Net à payer et masse salariale du salarié */
            ->setNetPayer($netPayer)
            ->setAmountMensualityPret($amountMensualityPret)
            ->setAmountMensuelAcompt($amountMensuelAcompt)
            ->setMasseSalary($masseSalaried)
            ->setStatus(Status::EN_ATTENTE);


        /** Enregistrement du livre de paie */

        $this->manager->persist($payroll);
        $this->manager->persist($salary);

        $conge = $this->congeRepository->findCongeByPersonal($personal->getId());
        if (!$conge?->getStatus() && ($conge?->getTypeConge() === Status::EFFECTIF or $conge?->getTypeConge() === Status::PARTIEL) && $conge?->getTypePayementConge() === Status::IMMEDIAT) {
            $conge->setStatus(Status::PAYE);
        } elseif (!$conge?->getStatus() && ($conge?->getTypeConge() === Status::EFFECTIF or $conge?->getTypeConge() === Status::PARTIEL) && $conge?->getTypePayementConge() === Status::ULTERIEUR) {
            $conge->setStatus(Status::IMPAYEE);
        }

        return $payroll;
    }


    /** Remplire le dictionnaire de paie pour les salariées dont la date d'embauche est inclus dans la periode de paie
     * @throws Exception
     */
    public function setProrataPayroll(Personal $personal, Campagne $campagne): Payroll
    {
        /** Nombre de jour travailler pendant la periode current de paie */
        $dayOfPresence = $this->paieByPeriodService->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne)['nb_jour_presence'];

        /** Ajouter les Informations utile du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') - ' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();

        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $personal->getSalary();
        $salaire = $this->paieByPeriodService->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $baseSalaire = ceil((double)$salaire['salaire_categoriel']);
        $sursalaire = ceil((double)$salary->getSursalaire() * $dayOfPresence / 30);
        $majorationHeursSupp = round($this->paieByPeriodService->amountHeureSuppProrata($personal, $campagne));

        /** Ajouter toutes les primes possible  */
        $primeFonctions = ceil($this->primeService->getPrimeFonction($personal) * $dayOfPresence / 30);
        $primeLogements = ceil($this->primeService->getPrimeLogement($personal) * $dayOfPresence / 30);
        $indemniteFonctions = ceil($this->primeService->getIndemniteFonction($personal) * $dayOfPresence / 30);
        $indemniteLogements = ceil($this->primeService->getIndemniteLogement($personal) * $dayOfPresence / 30);
        $primeTransportLegal = ceil($this->primeService->getPrimeTransportLegal() * $dayOfPresence / 30);
        $primeTransportImposable = ceil(((double)($salary->getPrimeTransport() * $dayOfPresence / 30) - $primeTransportLegal));
        $primePaniers = ceil($this->primeService->getPrimePanier($personal) * $dayOfPresence / 30);
        $primeSalissures = ceil($this->primeService->getPrimeSalissure($personal) * $dayOfPresence / 30);
        $primeTenueTravails = ceil($this->primeService->getPrimeTT($personal) * $dayOfPresence / 30);
        $primeOutillages = ceil($this->primeService->getPrimeOutil($personal) * $dayOfPresence / 30);
        $primeRendement = ceil($this->primeService->getPrimeRendement($personal) * $dayOfPresence / 30);

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage() * $dayOfPresence / 30);
        $avantageNatureImposable = round(((double)($salary?->getAmountAventage() * $dayOfPresence / 30) - $avantageNonImposable));

        /** Ajouter les charges du salarié ( retenues fiscales et sociales) */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = round($chargePersonal?->getNumPart(), 1);
        $salaryIts = ceil($chargePersonal?->getAmountIts());
        $salaryCnps = ceil($chargePersonal?->getAmountCNPS());
        $salaryCmu = ceil($chargePersonal?->getAmountCMU());
        $assuranceSanteSalariale = $this->paieByPeriodService->amountAssuranceSante($personal)['assurance_salariale'];
        $amountChargFiscalPersonal = $salaryIts;
        $amountChargSocialPersonal = $salaryCnps + $salaryCmu + $assuranceSanteSalariale;
        $chargeSalarie = ceil($amountChargFiscalPersonal + $amountChargSocialPersonal);

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
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp + $primeFonctions + $primeLogements
            + $indemniteFonctions + $indemniteLogements + $primeTransportImposable + $avantageNatureImposable
            + $primeTransportLegal + $avantageNonImposable;


        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp + $primeFonctions + $primeLogements
            + $indemniteFonctions + $indemniteLogements + $primeTransportImposable + $avantageNatureImposable;


        /** Ajouter les prêt et account qui constitue des retenues sur salaire net */
        $amountMensualityPret = $this->paieByPeriodService->amountPretCampagne($personal);
        $amountMensuelAcompt = $this->paieByPeriodService->amountAcomptCampagne($personal);

        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = ceil(($netImposable + $primeTransportLegal + $avantageNonImposable + $primeTenueTravails + $primeSalissures + $primeOutillages + $primeRendement + $primePaniers) - ($chargeSalarie + $amountMensuelAcompt + $amountMensualityPret));

        /** Ajouter la masse salariale */
        $masseSalaried = $netPayer + $chargePatronal + $chargeSalarie;

        /** Remplire le dictionnaire */
        $payroll = (new Payroll())
            ->setCampagne($campagne)
            ->setPersonal($personal)
            ->setNumberPart($nombrePart)
            ->setDayOfPresence($dayOfPresence)
            ->setMatricule($matricule)
            ->setService($service)
            ->setCategories($categorie)
            ->setDepartement($departement)
            ->setDateEmbauche($dateEmbauche)
            ->setNumCnps($numeroCnps)
            /** element de salaire */
            ->setBaseAmount($baseSalaire)
            ->setSursalaire($sursalaire)
            ->setMajorationAmount($majorationHeursSupp)
            ->setBrutAmount($salaireBrut)
            ->setImposableAmount($netImposable)
            ->setNetPayer($netPayer)
            ->setMasseSalary($masseSalaried)
            /** les primes */
            ->setPrimeFonctionAmount($primeFonctions)
            ->setPrimeLogementAmount($primeLogements)
            ->setIndemniteFonctionAmount($indemniteFonctions)
            ->setIndemniteLogementAmount($indemniteLogements)
            ->setSalaryTransport($primeTransportLegal)
            ->setAmountTransImposable($primeTransportImposable)
            ->setAmountPrimePanier($primePaniers)
            ->setAmountPrimeSalissure($primeSalissures)
            ->setAmountPrimeTenueTrav($primeTenueTravails)
            ->setAmountPrimeOutillage($primeOutillages)
            ->setAmountPrimeRendement($primeRendement)
            /** les aventages en natures */
            ->setAmountAvantageImposable($avantageNatureImposable)
            ->setAventageNonImposable($avantageNonImposable)
            /** charge salariale */
            ->setSalaryIts($salaryIts)
            ->setSalaryCnps($salaryCnps)
            ->setSalaryCmu($salaryCmu)
            ->setSalarySante($assuranceSanteSalariale)
            ->setFixcalAmount($amountChargFiscalPersonal)
            ->setSocialAmount($amountChargSocialPersonal)
            ->setTotalRetenueSalarie($chargeSalarie)
            /** charge patronale */
            ->setEmployeurIs($employeurIS)
            ->setEmployeurCr($employeurCR)
            ->setEmployeurCmu($employeurCMU)
            ->setAmountTA($employeurTA)
            ->setAmountFPC($employeurFPC)
            ->setAmountAnnuelFPC($employeurFPCAnnuel)
            ->setEmployeurFdfp($employeurTA + $employeurFPC + $employeurFPCAnnuel)
            ->setEmployeurPf($employeurPF)
            ->setEmployeurAt($employeurAT)
            ->setEmployeurCnps($employeurCR + $employeurPF + $employeurAT)
            ->setEmployeurSante($assuranceSantePatronale)
            ->setFixcalAmountEmployeur($amountChargFiscalPatronale)
            ->setSocialAmountEmployeur($amountChargSocialPatronale)
            ->setTotalRetenuePatronal($chargePatronal)
            ->setAmountMensualityPret($amountMensualityPret)
            ->setAmountMensuelAcompt($amountMensuelAcompt)
            ->setStatus(Status::EN_ATTENTE);
        /** Enregistrement du livre de paie */
        $this->manager->persist($payroll);

        return $payroll;
    }

    /**
     * Calculer le salaire dû d'une personne qui à travailler le mois passé et n'a pas été payer et aussi celui du mois en cours
     * @throws Exception
     */
    public function setExtraMonthPayroll(Personal $personal, Campagne $campagne): Payroll
    {
        //Nombre de jour pour le mois passé
        /** Nombre de jour travailler pendant la periode current de paie et celle du mois non payée */
        $dayOfCurrentMonth = $this->paieProrataService->amountBrutAndAmountImposableAndAmountCategoriel($personal, $campagne)['day_of_presence'];

        /** Ajouter les Informations utile du salarié */
        $matricule = $personal->getMatricule();
        $service = $personal->getService();
        $categorie = '(' . $personal->getCategorie()->getCategorySalarie()->getName() . ') - ' . $personal->getCategorie()->getIntitule();
        $departement = $personal->getFonction();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $numeroCnps = $personal->getRefCNPS();


        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $personal->getSalary();
        $salaire = $this->paieProrataService->amountBrutAndAmountImposableAndAmountCategoriel($personal, $campagne);
        $baseSalaire = ceil((double)$salaire['salaire_categoriel']);
        $sursalaire = ceil((double)$salary->getSursalaire() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $majorationHeursSupp = ceil($this->paieProrataService->amountHeureSupplementaire($personal, $campagne));


        /** Ajouter toutes les primes possible  */
        $primeFonctions = ceil($this->primeService->getPrimeFonction($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeLogements = ceil($this->primeService->getPrimeLogement($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $indemniteFonctions = ceil($this->primeService->getIndemniteFonction($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $indemniteLogements = ceil($this->primeService->getIndemniteLogement($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeTransportLegal = ceil($this->primeService->getPrimeTransportLegal() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeTransportImposable = ($salary->getPrimeTransport() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) >= $primeTransportLegal
            ? ceil(((double)($salary->getPrimeTransport() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) - $primeTransportLegal)) : 0;
        $primePaniers = round($this->primeService->getPrimePanier($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeSalissures = round($this->primeService->getPrimeSalissure($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeTenueTravails = round($this->primeService->getPrimeTT($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeOutillages = round($this->primeService->getPrimeOutil($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $primeRendement = round($this->primeService->getPrimeRendement($personal) * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);

        /** Avantage en nature non imposable */
        $avantageNonImposable = round((double)$salary->getAvantage()?->getTotalAvantage() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK);
        $avantageNatureImposable = ($salary?->getAmountAventage() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) >= $avantageNonImposable ?
            round(((double)($salary->getAmountAventage() * $dayOfCurrentMonth / self::DAY_IN_MONTH_WORK) - $avantageNonImposable)) : 0;


        /** Ajouter les charges du salarié ( retenues fiscales et sociales) */
        $chargePersonal = $this->chargePersonalsRepository->findOneBy(['personal' => $personal]);
        $nombrePart = round($chargePersonal?->getNumPart(), 1);
        $salaryIts = round($chargePersonal?->getAmountIts());
        $salaryCnps = round($chargePersonal?->getAmountCNPS());
        $salaryCmu = round($chargePersonal?->getAmountCMU());
        $assuranceSanteSalariale = $this->paieProrataService->amountAssurance($personal)['assurance_salariale'];
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
        $assuranceSantePatronale = $this->paieProrataService->amountAssurance($personal)['assurance_patronale'];
        $amountChargFiscalPatronale = $employeurIS + $employeurFPC + $employeurFPCAnnuel + $employeurTA;
        $amountChargSocialPatronale = $employeurCMU + $employeurCR + $employeurAT + $employeurPF + $assuranceSantePatronale;
        $chargePatronal = round($amountChargFiscalPatronale + $amountChargSocialPatronale);

        /** Ajouter le salaire brut qui constitue l'ensemble des élements de salaire imposable et non imposable */
        $salaireBrut = $baseSalaire + $sursalaire + $majorationHeursSupp
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements
            + $primeTransportImposable + $avantageNatureImposable + $primeTransportLegal + $avantageNonImposable;

        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $netImposable = $baseSalaire + $sursalaire + $majorationHeursSupp
            + $primeFonctions + $primeLogements + $indemniteFonctions + $indemniteLogements + $primeTransportImposable
            + $avantageNatureImposable;

        /** Ajouter les prêt et account qui constitue des retenues sur salaire net */
        $amountMensualityPret = $this->paieProrataService->amountPret($personal);
        $amountMensuelAcompt = $this->paieProrataService->amountAcompt($personal);

        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        $netPayer = ceil(($netImposable + $primeTransportLegal + $avantageNonImposable + $primePaniers + $primeRendement + $primeOutillages + $primeSalissures + $primeTenueTravails) - ($chargeSalarie + $amountMensualityPret + $amountMensuelAcompt));

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
            ->setCongesPayesAmount(null)
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
            /** Mensualiter du pret et acompt */
            ->setAmountMensualityPret($amountMensualityPret)
            ->setAmountMensuelAcompt($amountMensuelAcompt)
            /** Net à payer et masse salariale du salarié */
            ->setNetPayer($netPayer)
            ->setMasseSalary($masseSalaried)
            ->setStatus(Status::EN_ATTENTE);


        /** Enregistrement du livre de paie */

        $this->manager->persist($payroll);

        return $payroll;
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
    }
}