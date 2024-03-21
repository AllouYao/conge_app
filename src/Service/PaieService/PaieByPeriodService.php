<?php

namespace App\Service\PaieService;

use App\Entity\DossierPersonal\Personal;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Utils\Status;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class PaieByPeriodService
{
    public function __construct(
        private readonly HeureSupRepository                $heureSupRepository,
        private readonly AbsenceRepository                 $absenceRepository,
        private readonly CategoryChargeRepository          $categoryChargeRepository,
        private readonly RetenueForfetaireRepository       $forfetaireRepository,
        private readonly DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository
    )
    {
    }


    /** Obtenir le nombre de jour travailler pour les salariés qui ont été embauché dans le mois courant de la paie
     * @throws Exception
     */
    public function NbDayOfPresenceByDateEmbauche(Personal $personal): int|null
    {
        $contract = $personal->getContract();
        $dateEmbauche = $contract->getDateEmbauche();
        $anneeEmbauche = $dateEmbauche->format('Y');
        $moisEmbauche = $dateEmbauche->format('m');
        $anne = (int)$anneeEmbauche;
        $mois = (int)$moisEmbauche;
        $lastDayOfMonth = new DateTime(date('Y-m-t', mktime(0, 0, 0, $mois + 1, 0, $anne)));
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($dateEmbauche, $interval, $lastDayOfMonth);
        $dayOfPresence = [];
        foreach ($periode as $date) {
            $dayOfPresence[] = $date;
        }
        /** Obtenir le nombre de jour que fait la periode */
        return ceil(count($dayOfPresence));
    }

    /** Montant de la majoration des heures supplémentaire dans la periode de campagne */
    public function amountHeureSuppProrata(Personal $personal, Campagne $campagne): float|int
    {
        $majoration = 0;
        $dateDebut = $campagne->getDateDebut();
        $dateFin = $campagne->getDateFin();
        $heureSupps = $this->heureSupRepository->getHeureSupByPeriode($personal, $dateDebut, $dateFin);
        foreach ($heureSupps as $supp) {
            $majoration += $supp?->getAmount();
        }
        return $majoration;
    }

    /** Montant du brut et du brut imposable provisoire et en fonction des heure d'absence dans la periode de paie
     * @throws Exception
     */
    public function getProvisoireBrutAndBrutImpoCampagne(Personal $personal, Campagne $campagne): array
    {
        $dayOfPresence = $this->NbDayOfPresenceByDateEmbauche($personal);
        $date = $campagne->getDateDebut();
        $month = (int)$date->format('m');
        $year = (int)$date->format('Y');
        $actuelCategoriel = (int)$personal->getCategorie()?->getAmount();
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $year);
        $jour = 0;
        if ($absences) {
            foreach ($absences as $absence) {
                $nbAbsence = $absence->getEndedDate()->diff($absence->getStartedDate())->days;
                $jour += $nbAbsence;
                $dayOfPresence = $dayOfPresence - $jour;
            }
            $salaireCategoriel = ceil($actuelCategoriel * $dayOfPresence / 30);
            $salaireBrut = ceil((int)$personal->getSalary()?->getBrutAmount() * $dayOfPresence / 30);
            $brutImposable = ceil((int)$personal->getSalary()?->getBrutImposable() * $dayOfPresence / 30);
        } else {
            $salaireCategoriel = ceil($actuelCategoriel * $dayOfPresence / 30);
            $salaireBrut = ceil((int)$personal->getSalary()?->getBrutAmount() * $dayOfPresence / 30);
            $brutImposable = ceil((int)$personal->getSalary()?->getBrutImposable() * $dayOfPresence / 30);
        }
        return [
            'nb_jour_presence' => $dayOfPresence,
            'salaire_categoriel' => $salaireCategoriel,
            'brut_amount' => $salaireBrut,
            'brut_imposable_amount' => $brutImposable,
            'personal' => $personal
        ];
    }

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
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
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
    function amountCreditImpotCampagne(Personal $personal): float|int
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
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
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
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return ceil($netImposable * $categoryRate?->getValue() / 100);
    }

    /** Montant du taux d'apprentissage, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountTACampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
        $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return ceil($netImposable * $categoryRateFDFP_TA->getValue() / 100);
    }

    /** Montant de la FPC, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountFPCCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
        $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return ceil($netImposable * $categoryRateFDFP_FPC->getValue() / 100);
    }

    /** Montant de la FPC complement annuel de la periode de paie
     * @throws Exception
     */
    public function amountFPCAnnuelCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
        $categoryRateFDFP_FPC_VER = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return ceil($netImposable * $categoryRateFDFP_FPC_VER->getValue() / 100);
    }

    /** Montant de la caisse de retraite du salarie, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountCRCampagne(Personal $personal, Campagne $campagne): float|int
    {
        $salaire = $this->getProvisoireBrutAndBrutImpoCampagne($personal, $campagne);
        $amountHeuresSupp = $this->amountHeureSuppProrata($personal, $campagne);
        $netImposable = $salaire['brut_imposable_amount'] + $amountHeuresSupp;
        $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return ceil($netImposable * $categoryRateRCNPS_CR->getValue() / 100);
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
            'assurance_salariale' => (int)$salariale,
            'assurance_patronale' => (int)$patronale
        ];
    }

}