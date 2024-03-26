<?php

namespace App\Service\PaieService;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Repository\DevPaie\OperationRepository;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Service\AbsenceService;
use App\Utils\Status;
use Carbon\Carbon;
use Exception;

class PaieServices
{
    const NR_JOUR_TRAVAILLER = 30;

    public function __construct(
        private readonly HeureSupRepository                $heureSupRepository,
        private readonly CategoryChargeRepository          $categoryChargeRepository,
        private readonly RetenueForfetaireRepository       $forfetaireRepository,
        private readonly DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository,
        private readonly OperationRepository               $operationRepository,
        private readonly AbsenceRepository                 $absenceRepository,
        private readonly AbsenceService                 $absenceService
    )
    {
    }

    /** Montant du brut et du brut imposable provisoire et en fonction des heure d'absence dans la periode de paie
     * @throws Exception
     */
    public function getProvisoireBrutAndBrutImpoCampagne(Personal $personal, Campagne $campagne): array
    {
        $dayOfPresence = self::NR_JOUR_TRAVAILLER;
        $date = $campagne->getDateDebut();
        $month = (int)$date->format('m');
        $year = (int)$date->format('Y');
        $actuelCategoriel = (int)$personal->getCategorie()?->getAmount();
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);
        if ($absences) {
            $jour = 0;
            foreach ($absences as $absence) {
                $nbAbsence = $this->absenceService->countDays($absence);
                $jour += $nbAbsence;
            }

            $newDayOfPresence = $dayOfPresence - $jour;
            $salaireCategoriel = ceil($actuelCategoriel * $newDayOfPresence / self::NR_JOUR_TRAVAILLER);
            $salaireBrut = ceil((int)$personal->getSalary()?->getBrutAmount() * $newDayOfPresence / self::NR_JOUR_TRAVAILLER);
            $brutImposable = ceil((int)$personal->getSalary()?->getBrutImposable() * $newDayOfPresence / self::NR_JOUR_TRAVAILLER);
        } else {
            $newDayOfPresence = $dayOfPresence;
            $salaireCategoriel = $actuelCategoriel * $newDayOfPresence / self::NR_JOUR_TRAVAILLER;
            $salaireBrut = $personal->getSalary()?->getBrutAmount() * $newDayOfPresence / self::NR_JOUR_TRAVAILLER;
            $brutImposable = $personal->getSalary()?->getBrutImposable() * $newDayOfPresence / self::NR_JOUR_TRAVAILLER;
        }
        
        return [
            'day_of_presence' => $newDayOfPresence,
            'salaire_categoriel' => ceil($salaireCategoriel),
            'brut_amount' => ceil($salaireBrut),
            'brut_imposable_amount' => ceil($brutImposable)
        ];
    }

    /** Montant de la majoration des heures supplémentaire dans la periode de campagne */
    public function getHeureSuppCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $majoration = 0;
        $dateDebut = $campagne->getDateDebut();
        $dateFin = $campagne->getDateFin();
        $heureSupps = $this->heureSupRepository->getHeureSupByPeriode($personal, $dateDebut, $dateFin);
        foreach ($heureSupps as $supp) {
            $majoration += $supp?->getAmount();
        }
        return ceil($majoration);
    }

    /** Montant de la prime d'ancienneté dans la periode de campagne
     * @throws Exception
     */
    public function getPrimeAncienneteCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaireCategoriel = (int)$this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne)['salaire_categoriel'];
        $anciennete = (double)$personal->getOlder();
        if ($anciennete >= 2 && $anciennete < 3) {
            $primeAnciennete = $salaireCategoriel * 2 / 100;
        } elseif ($anciennete >= 3 && $anciennete <= 25) {
            $primeAnciennete = ($salaireCategoriel * $anciennete) / 100;
        } elseif ($anciennete >= 26) {
            $primeAnciennete = ($salaireCategoriel * 25) / 100;

        } else {
            $primeAnciennete = 0;
        }
        return ceil($primeAnciennete);
    }

    /** Montant du congés payés dans la periode de campagne */
    // Ajouter ici la fonction qui nous permet d'obtenir le montant de l'allocation en fonction du mois de campagne.

    /** Montant du nombre de part pour les salarié de la periode de campagne */
    public function getPartCampagne(Personal $personal): float|int
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


    /** Montant de l'impôts brut sur le salaire, charge salarial de la periode de paie
     * @throws Exception
     */
    public function amountImpotBrutCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
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

    /** Montant du crédit d'impôt à déduit sur l'impôts brut, charge salarial de la periode de paie */
    public function amountCreditImpotCampagne(Personal $personal): float|int
    {
        $nbrePart = $this->getPartCampagne($personal);
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

    /** Montant de la retraite générale, charge salarial de la periode de paie
     * @throws Exception
     */
    public function amountCNPSCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaireBrut = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $salaireBrut['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        if ($netImposable > 1647314) {
            $netImposable = 1647314;
        }
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return ceil($netImposable * $categoryRate->getValue() / 100);
    }

    /** Montant de la couverture maladie universelle du salarie, charge salarial de la periode de paie */
    public function amountCMUCampagne(Personal $personal): float|int
    {
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();
        $marie = $personal->getEtatCivil() === Status::MARIEE ? 1 : 0;
        $CMU = $categoryRate->getValue();
        return ($chargePeople * $CMU) + ($CMU * $marie) + $CMU;
    }

    /** Montant de la couverture maladie universelle du salarie, charge patronal de la periode de paie */
    public function amountCMUEmpCampagne(): float|int
    {
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        return (int)$categoryRate->getValue();
    }

    /** Determiner le montant de la part patronal I.S locaux, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountISCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $amountBrut = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return ceil($amountBrut * $categoryRate?->getValue() / 100);
    }

    /** Montant du taux d'apprentissage, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountTACampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $amountBrut = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return ceil($amountBrut * $categoryRateFDFP_TA->getValue() / 100);
    }

    /** Montant de la FPC, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountFPCCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $amountBrut = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return ceil($amountBrut * $categoryRateFDFP_FPC->getValue() / 100);
    }

    /** Montant de la FPC complement annuel de la periode de paie
     * @throws Exception
     */
    public function amountFPCAnnuelCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $amountBrut = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC_VER = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return ceil($amountBrut * $categoryRateFDFP_FPC_VER->getValue() / 100);
    }

    /** Montant de la caisse de retraite du salarie, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountCRCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $majorationHeursSupp = $this->getHeureSuppCampagne($personal, $campagne);
        $primeAnciennete = $this->getPrimeAncienneteCampagne($personal, $campagne);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $amountBrut = $salaire['brut_imposable_amount'] + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return ceil($amountBrut * $categoryRateRCNPS_CR->getValue() / 100);
    }

    /** Montant de la prestation familliale du salarie, charge patronal de la periode de paie */
    public function amountPFCampagne(Personal $personal): float|int
    {
        $smig = (int)$personal->getSalary()->getSmig();
        $categoryRateRCNPS_PF = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF']);
        return ceil($smig * $categoryRateRCNPS_PF->getValue() / 100);
    }

    /** Montant de l'accident de travail du salarie, charge patronal de la periode de paie */
    public function amountATCampagne(Personal $personal): float|int
    {
        $smig = (int)$personal->getSalary()->getSmig();
        $categoryRateRCNPS_AT = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT']);
        return ceil($smig * $categoryRateRCNPS_AT->getValue() / 100);
    }

    /** Montant de l'assurance sante part salariale et patronale de la periode de paie */
    public function amountAssuranceSante(Personal $personal): array
    {
        $assuranceClassic = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_CLASSIC]);
        $assuranceFamille = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_FAMILLE]);
        $amountForfetaireClassic = $this->detailRetenueForfetaireRepository->findRetenueForfetaire($personal, $assuranceClassic);
        $amountForfetaireFamille = $this->detailRetenueForfetaireRepository->findRetenueForfetaire($personal, $assuranceFamille);
        $salariale = 0;
        $patronale = 0;

        if ($amountForfetaireClassic) {
            $salariale = $amountForfetaireClassic->getAmount();
            $patronale = $amountForfetaireClassic->getAmountEmp();
        } elseif ($amountForfetaireFamille) {
            $salariale = $amountForfetaireFamille->getAmount();
            $patronale = $amountForfetaireFamille->getAmountEmp();
        }

        return [
            'assurance_salariale' => $salariale,
            'assurance_patronale' => $patronale
        ];
    }

    /** Regularisation retenue ou remboursement pour les salariés */
    public function getRegulRemboursement(Personal $personal): array
    {
        $today = Carbon::today();
        $remboursement = $this->operationRepository->findOperationByPersonal(Status::REMBOURSEMENT, Status::VALIDATED, $personal, $today->month, $today->year);
        $remboursementNet = $remboursement?->getAmountNet();
        $remboursementBrut = $remboursement?->getAmountBrut();

        return [
            'remboursement_net' => (int)$remboursementNet,
            'remboursement_brut' => (int)$remboursementBrut
        ];
    }

    public function getRegulRetenue(Personal $personal): array
    {
        $today = Carbon::today();
        $retenue = $this->operationRepository->findOperationByPersonal(Status::RETENUES, Status::VALIDATED, $personal, $today->month, $today->year);
        $retenueNet = $retenue?->getAmountNet();
        $retenueBrut = $retenue?->getAmountBrut();
        return [
            'retenue_brut' => (int)$retenueBrut,
            'retenue_net' => (int)$retenueNet
        ];
    }

}