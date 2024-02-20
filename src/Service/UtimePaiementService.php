<?php

namespace App\Service;

use App\Entity\DossierPersonal\Personal;
use App\Repository\DossierPersonal\DetailPrimeSalaryRepository;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\DetailSalaryRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use Carbon\Carbon;

class UtimePaiementService
{
    private AbsenceService $absenceService;
    private CategoryChargeRepository $categoryChargeRepository;
    private PrimesRepository $primesRepository;
    private DetailSalaryRepository $detailSalaryRepository;
    private DetailPrimeSalaryRepository $detailPrimeSalaryRepository;
    private RetenueForfetaireRepository $forfetaireRepository;
    private DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository;

    public function __construct(
        AbsenceService                    $absenceService,
        CategoryChargeRepository          $categoryChargeRepository,
        PrimesRepository                  $primesRepository,
        DetailSalaryRepository            $detailSalaryRepository,
        DetailPrimeSalaryRepository       $detailPrimeSalaryRepository,
        RetenueForfetaireRepository       $forfetaireRepository,
        DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository
    )
    {
        $this->absenceService = $absenceService;
        $this->categoryChargeRepository = $categoryChargeRepository;
        $this->primesRepository = $primesRepository;
        $this->detailSalaryRepository = $detailSalaryRepository;
        $this->detailPrimeSalaryRepository = $detailPrimeSalaryRepository;
        $this->forfetaireRepository = $forfetaireRepository;
        $this->detailRetenueForfetaireRepository = $detailRetenueForfetaireRepository;
    }

    /** Montant de la majoration des heures supplémentaire */
    public function getAmountMajorationHeureSupp(Personal $personal): float|int
    {
        $majoration = 0;
        $heureSupps = $personal->getHeureSups();
        foreach ($heureSupps as $supp) {
            $majoration += $supp->getAmount();
        }
        return $majoration;
    }

    /** Montant de la prime d'anciènneté */
    public function getAmountAnciennete(Personal $personal): float|int
    {
        $salaireCategoriel = (int)$personal->getCategorie()->getAmount();
        $anciennete = (double)$personal->getOlder();
        switch ($anciennete) {
            case $anciennete >= 2 && $anciennete < 3:
                $primeAnciennete = $salaireCategoriel * 2 / 100;
                break;
            case $anciennete >= 3 && $anciennete <= 25:
                $primeAnciennete = ($salaireCategoriel * $anciennete) / 100;
                break;
            case $anciennete >= 26:
                $primeAnciennete = ($salaireCategoriel * 25) / 100;
                break;
            default:
                $primeAnciennete = 0;
        }
        return $primeAnciennete;
    }

    /** Montant du congés payés */
    public function getAmountCongesPayes(Personal $personal): float|int
    {
        $congesPayes = 0;
        $conges = $personal->getConges();
        foreach ($conges as $conge) {
            $congesPayes = $conge->getAllocationConge();
        }
        return $congesPayes;
    }

    /** Obtenir le salaire brut et brut imposable à partir du salaire catégoriel si Oui ou Non il y à une absence */
    public function getAmountSalaireBrutAndImposable(Personal $personal): array
    {
        $date = Carbon::today();
        $categorielWithAbsence = $this->absenceService->getAmountByMonth($personal, $date->month, $date->year);
        $actuelCategoriel = $personal->getCategorie()->getAmount();
        if ($personal->getAbsences()->count() > 0) {
            $salaireCategoriel = $categorielWithAbsence;
            $salaireBrut = $personal->getSalary()->getBrutAmount() - $actuelCategoriel + (int)$salaireCategoriel;
            $brutImposable = $personal->getSalary()->getBrutImposable() - $actuelCategoriel + (int)$categorielWithAbsence;
        } else {
            $salaireCategoriel = $actuelCategoriel;
            $salaireBrut = $personal->getSalary()->getBrutAmount();
            $brutImposable = $personal->getSalary()->getBrutImposable();
        }
        return [
            'salaire_categoriel' => $salaireCategoriel,
            'brut_amount' => $salaireBrut,
            'brut_imposable_amount' => $brutImposable
        ];
    }

    /** Obtenir le nombre de part du salarie */
    public function getNumberParts(Personal $personal): float|int
    {
        $nbrePart = [
            'CELIBATAIRE' => 1,
            'MARIE' => 2,
            'VEUF' => 1,
            'DIVORCE' => 1
        ];
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();

        //Ici je voudrais mouvementé le nbre de part du salarié en fonction du nombre d'enfant à charge
        if ($personal->getEtatCivil() === 'CELIBATAIRE' || $personal->getEtatCivil() === 'DIVORCE') {
            return match (true) {
                $chargePeople == 1 => 2,
                $chargePeople == 2 => 2.5,
                $chargePeople == 3 => 3,
                $chargePeople == 4 => 3.5,
                $chargePeople == 5 => 4,
                $chargePeople == 6 => 4.5,
                $chargePeople > 6 => 5,
                default => 1,
            };
        } elseif ($personal->getEtatCivil() === 'MARIE') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 2,
            };
        } elseif ($personal->getEtatCivil() === 'VEUF') {
            return match (true) {
                $chargePeople == 1 => 2.5,
                $chargePeople == 2 => 3,
                $chargePeople == 3 => 3.5,
                $chargePeople == 4 => 4,
                $chargePeople == 5 => 4.5,
                $chargePeople >= 6 => 5,
                default => 1,
            };
        }
        return $nbrePart[$personal->getEtatCivil()];
    }

    /** Determiner le montant de l'impôts brut sur le salaire, charge salarial */
    public function calculerAmountImpotBrut(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $netImposable = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $tranchesImposition = [
            ['min' => 0, 'limite' => 75000, 'taux' => 0],
            ['min' => 75001, 'limite' => 240000, 'taux' => 0.16],
            ['min' => 240001, 'limite' => 800000, 'taux' => 0.21],
            ['min' => 800001, 'limite' => 2400000, 'taux' => 0.24],
            ['min' => 2400001, 'limite' => 8000000, 'taux' => 0.28],
            ['min' => 8000001, 'limite' => PHP_INT_MAX, 'taux' => 0.32],
        ];

        $impotBrut = 0;

        foreach ($tranchesImposition as $tranche) {
            $limiteMin = $tranche['min'];
            $limiteMax = $tranche['limite'];
            $taux = $tranche['taux'];

            if ($netImposable > $limiteMin && $netImposable >= $limiteMax) {
                $montantImposable = ($limiteMax - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
            } else if ($netImposable > $limiteMin) {
                $montantImposable = ($netImposable - $limiteMin) * $taux;
                $impotBrut += $montantImposable;
                break;
            }
        }

        return $impotBrut;
    }

    /** Determiner le montant du crédit d'impôt à déduit sur l'impôts brut, charge salarial */
    function calculateAmountCreditImpot(Personal $personal): float|int
    {
        $nbrePart = $this->getNumberParts($personal);
        $creditImpot = null;
        switch ($nbrePart) {
            case 1;
                $creditImpot = 0;
                break;
            case 1.5;
                $creditImpot = 5500;
                break;
            case 2;
                $creditImpot = 11000;
                break;
            case 2.5;
                $creditImpot = 16500;
                break;
            case 3;
                $creditImpot = 22000;
                break;
            case 3.5;
                $creditImpot = 27500;
                break;
            case 4;
                $creditImpot = 33000;
                break;
            case 4.5;
                $creditImpot = 38500;
                break;
            case 5;
                $creditImpot = 44000;
                break;
        }
        return $creditImpot;
    }

    /** Determiner le montant de la retraite générale du salarie, charge salarial */
    public function calculateAmountCNPS(Personal $personal): float
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $netImposable = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        if ($netImposable > 1647314) {
            $netImposable = 1647314;
        }
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return $netImposable * $categoryRate->getValue() / 100;
    }

    /** Determiner le montant de la couverture maladie universelle du salarie, charge salarial */
    public function calculateAmountCMU(Personal $personal): float|int
    {
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();
        $marie = $personal->getEtatCivil() === Status::MARIEE ? 1 : 0;
        $CMU = $categoryRate->getValue();
        return ($chargePeople * $CMU) + ($CMU * $marie) + $CMU;
    }

    /** Determiner le montant de la part patronal I.S locaux, charge patronal */
    public function calculateAmountIS(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $amountBrut = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return $amountBrut * $categoryRate?->getValue() / 100;
    }

    /** Determiner le montant du taux d'apprentissage, charge patronal */
    public function calculateAmountTA(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $amountBrut = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return $amountBrut * $categoryRateFDFP_TA->getValue() / 100;
    }

    /** Determiner le montant de la FPC, charge patronal */
    public function calculateAmountFPC(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $amountBrut = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return $amountBrut * $categoryRateFDFP_FPC->getValue() / 100;
    }

    /** Determiner le montant de la FPC complement annuel */
    public function calculateAmountFPCAnnuel(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $amountBrut = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC_VER = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return $amountBrut * $categoryRateFDFP_FPC_VER->getValue() / 100;
    }

    /** Determiner le montant de la caisse de retraite du salarie, charge patronal */
    public function calculateAmountRCNPS_CR(Personal $personal): float|int
    {
        $salaireBrut = $this->getAmountSalaireBrutAndImposable($personal);
        $majorationHeursSupp = $this->getAmountMajorationHeureSupp($personal);
        $primeAnciennete = $this->getAmountAnciennete($personal);
        $congesPayes = $this->getAmountCongesPayes($personal);
        $amountBrut = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return $amountBrut * $categoryRateRCNPS_CR->getValue() / 100;
    }

    /** Determiner le montant de la prestation familliale du salarie, charge patronal */
    public function calculateAmountRCNPS_PF(Personal $personal): float|int
    {
        $smig = $personal->getSalary()->getSmig();
        $categoryRateRCNPS_PF = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF']);
        return $smig * $categoryRateRCNPS_PF->getValue() / 100;
    }

    /** Determiner le montant de l'accident de travail du salarie, charge patronal */
    public function calculateAmountRCNPS_AT(Personal $personal): float|int
    {
        $smig = $personal->getSalary()->getSmig();
        $categoryRateRCNPS_AT = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT']);
        return $smig * $categoryRateRCNPS_AT->getValue() / 100;
    }

    /** Determiner la prime de panier non imposable */
    public function getPrimePanier(Personal $personal): float|int
    {
        $primePanier = $this->primesRepository->findOneBy(['code' => Status::PRIME_PANIER]);
        $amountPanier = $this->detailSalaryRepository->findPrime($personal, $primePanier);
        return (int)$amountPanier?->getAmountPrime();
    }

    /** Determiner la prime de salissure non imposable */
    public function getPrimeSalissure(Personal $personal): float|int
    {
        $primeSalissure = $this->primesRepository->findOneBy(['code' => Status::PRIME_SALISSURE]);
        $amountSalissure = $this->detailSalaryRepository->findPrime($personal, $primeSalissure);
        return (int)$amountSalissure?->getAmountPrime();
    }

    /** Determiner la prime de tenue de travail non imposable */
    public function getPrimeTT(Personal $personal): float|int
    {
        $primeTT = $this->primesRepository->findOneBy(['code' => Status::PRIME_TENUE_TRAVAIL]);
        $amountTT = $this->detailSalaryRepository->findPrime($personal, $primeTT);
        return (int)$amountTT?->getAmountPrime();
    }

    /** Determiner la prime d'outillage non imposable */
    public function getPrimeOutil(Personal $personal): float|int
    {
        $primeOutil = $this->primesRepository->findOneBy(['code' => Status::PRIME_OUTILLAGE]);
        $amountOutil = $this->detailSalaryRepository->findPrime($personal, $primeOutil);
        return (int)$amountOutil?->getAmountPrime();
    }

    /** Determiner la prime de rendement non imposable */
    public function getPrimeRendement(Personal $personal): float|int
    {
        $primeRendement = $this->primesRepository->findOneBy(['code' => Status::PRIME_RENDEMENT]);
        $amountRendement = $this->detailSalaryRepository->findPrime($personal, $primeRendement);
        return (int)$amountRendement?->getAmountPrime();
    }

    /** Determiner la prime de fonction imposable */
    public function getPrimeFonction(Personal $personal): float|int
    {
        $primeFonction = $this->primesRepository->findOneBy(['code' => Status::PRIME_FONCTION]);
        $amountFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeFonction);
        return (int)$amountFonction?->getAmount();
    }

    /** Determiner la prime de logement imposable */
    public function getPrimeLogement(Personal $personal): float|int
    {
        $primeLogement = $this->primesRepository->findOneBy(['code' => Status::PRIME_LOGEMENT]);
        $amountLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $primeLogement);
        return (int)$amountLogement?->getAmount();
    }

    /** Determiner l'indemnite de fonction imposable */
    public function getIndemniteFonction(Personal $personal): float|int
    {
        $indemniteFonction = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_FONCTION]);
        $amountIndemFonction = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteFonction);
        return (int)$amountIndemFonction?->getAmount();
    }

    /** Determiner l'indemnite de logement imposable */
    public function getIndemniteLogement(Personal $personal): float|int
    {
        $indemniteLogement = $this->primesRepository->findOneBy(['code' => Status::INDEMNITE_LOGEMENTS]);
        $amountIndemLogement = $this->detailPrimeSalaryRepository->findPrimes($personal, $indemniteLogement);
        return (int)$amountIndemLogement?->getAmount();
    }

    /** Determiner prime de transport non imposable */
    public function getPrimeTransportLegal(): float|int
    {
        $primeTransport = $this->primesRepository->findOneBy(['code' => Status::PRIME_TRANSPORT]);
        return (int)$primeTransport->getTaux();
    }

    public function getAssuranceSante(Personal $personal): array
    {
        $retenueForfetaireSalariale = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_SANTE_SALARIALE]);
        $retenueForfetairePatronale = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_SANTE_PATRONALE]);
        $amountForfetaire = $this->detailRetenueForfetaireRepository->findRetenueForfetaire($personal, $retenueForfetaireSalariale);

        if ($amountForfetaire != null) {
            $amountRfSalariale = $amountForfetaire?->getAmount();
            $amountRfPatronale = $retenueForfetairePatronale?->getValue();
        } else {
            $amountRfSalariale = 0;
            $amountRfPatronale = 0;
        }

        return [
            'assurance_salariale' => $amountRfSalariale,
            'assurance_patronale' => $amountRfPatronale
        ];
    }
}