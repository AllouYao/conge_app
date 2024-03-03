<?php

namespace App\Service\CasExeptionel;

use App\Entity\DossierPersonal\Departure;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Service\AbsenceService;
use App\Utils\Status;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class DepartureCampagneService
{
    public function __construct(
        private readonly AbsenceService                    $absenceService,
        private readonly HeureSupRepository                $heureSupRepository,
        private readonly CategoryChargeRepository          $categoryChargeRepository,
        private readonly RetenueForfetaireRepository       $forfetaireRepository,
        private readonly DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository
    )
    {
    }

    public function amountSalaireBrutAndImposable(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $date = $departure->getDate();
        $month = (int)$date->format('m');
        $year = (int)$date->format('Y');
        $categorielWithAbsence = $this->absenceService->getAmountByMonth($personal, $month, $year);
        $actuelCategoriel = (int)$personal->getCategorie()->getAmount();
        if ($personal->getAbsences()->count() > 0) {
            $salaireCategoriel = $categorielWithAbsence;
            $salaireBrut = $personal->getSalary()->getBrutAmount() - $actuelCategoriel + $salaireCategoriel;
            $brutImposable = $personal->getSalary()->getBrutImposable() - $actuelCategoriel + $categorielWithAbsence;
        } else {
            $salaireCategoriel = $actuelCategoriel;
            $salaireBrut = $personal->getSalary()->getBrutAmount();
            $brutImposable = $personal->getSalary()->getBrutImposable();
        }
        return [
            'salaire_categoriel' => $salaireCategoriel,
            'brut_amount' => round($salaireBrut, 2),
            'brut_imposable_amount' => round($brutImposable, 2)
        ];
    }

    /** Obtenir le nombre de jour de présence depuis le premier jour du mois actuel jusqu'au jour du départ de l'entreprise */
    public function NbDayOfPresenceBeforeDeparture(Departure $departure): float|int|null
    {
        /** Obtenir les jours précédent le jour du départ dépuis le premier jours du mois de licenciement de l'année */
        $dateDepart = $departure->getDate();
        $anneeDepart = $dateDepart->format('Y');
        $moisDepart = $dateDepart->format('m');
        $annee = (int)$anneeDepart;
        $mois = (int)$moisDepart;
        $firstDayOfYear = new DateTime("$annee-$mois-01");
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($firstDayOfYear, $interval, $dateDepart);
        $day = [];
        foreach ($periode as $date) {
            $day[] = $date;
        }
        /** Obtenir le nombre de jours de presence que fait la période */
        return ceil(count($day));
    }

    /** Obtenir le nombre de jour de présence depuis le premier jour du mois actuel jusqu'au dernier jour du mois
     * @throws Exception
     */
    public function NbDayOfPresenceByCurrentMonth(Departure $departure): float|int|null
    {
        /** Obtenir les jours précédent le jour du départ dépuis le premier jours du mois de licenciement de l'année */
        $dateDepart = $departure->getDate();
        $anneeDepart = $dateDepart->format('Y');
        $moisDepart = $dateDepart->format('m');
        $annee = (int)$anneeDepart;
        $mois = (int)$moisDepart;
        $firstDayOfMonth = new DateTime("$annee-$mois-01");
        $lastDayOfMonth = new DateTime(date('Y-m-t', mktime(0, 0, 0, $mois + 1, 0, $annee)));
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($firstDayOfMonth, $interval, $lastDayOfMonth);
        $day = [];
        foreach ($periode as $date) {
            $day[] = $date;
        }
        /** Obtenir le nombre de jours de presence que fait la période */
        return ceil(count($day) + 1);
    }

    /** Determiner le salaire de base en fonction des jour travailler dans le mois
     * @throws Exception
     */
    public function baseAmountByNbDayOfPresence(Departure $departure): float|int|null
    {
        /** Salaire catégoriel en fonction du nombre de jour travail avant le départ de l'entreprise **/
        $salaireCategoriel = $this->amountSalaireBrutAndImposable($departure)['salaire_categoriel']; // le salaire de base du salarié concerné
        $nbDayOfPresence = $this->NbDayOfPresenceBeforeDeparture($departure); // nombre de jour travailler du 1er jusqu'a la date de depart
        $nbDayOfMonth = $this->NbDayOfPresenceByCurrentMonth($departure); // nombre de jour travailler du 1er jusqu'a la date de depart
        return round($salaireCategoriel * $nbDayOfPresence / $nbDayOfMonth);
    }

    /**
     * Determiner le netImposable en fonction des jour travailler dans le mois
     * @throws Exception
     */
    public function netImposableByNbDayOfPresence(Departure $departure): float|int|null
    {
        /** Salaire catégoriel en fonction du nombre de jour travail avant le départ de l'entreprise **/
        $netImposable = $this->amountSalaireBrutAndImposable($departure)['brut_imposable_amount']; // le salaire de base du salarié concerné
        $nbDayOfPresence = $this->NbDayOfPresenceBeforeDeparture($departure); // nombre de jour travailler du 1er jusqu'a la date de depart
        $nbDayOfMonth = $this->NbDayOfPresenceByCurrentMonth($departure); // nombre de jour travailler du 1er jusqu'a la date de depart
        return round($netImposable * $nbDayOfPresence / $nbDayOfMonth);
    }

    /** Determiner la majoration des heures supplémentaire dans la periode de campagne */
    public function amountHeureSuppByCampagneAndDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $personal = $departure->getPersonal();
        $majoration = 0;
        $dateDebut = $campagne->getDateDebut();
        $dateFin = $campagne->getDateFin();
        $heureSupps = $this->heureSupRepository->getHeureSupByPeriode($personal, $dateDebut, $dateFin);
        foreach ($heureSupps as $supp) {
            $majoration += $supp?->getAmount();
        }
        return $majoration;
    }

    /** Determiner la prime d'ancienneté dans la periode de campagne
     * @throws Exception
     */
    public function amountPrimeAncienneteCampagneByDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $salaireCategoriel = (int)$this->baseAmountByNbDayOfPresence($departure);
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
        return $primeAnciennete;
    }

    /** Montant du nombre de part pour les salarié de la periode de campagne */
    public function nbPartCampagneByDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
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
    public function amountImpotBrutCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
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
    function amountCreditImpotCampagneByDeparture(Departure $departure): float|int
    {
        $nbrePart = $this->nbPartCampagneByDeparture($departure);
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
    public function amountCNPSCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        if ($netImposable > 1647314) {
            $netImposable = 1647314;
        }
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return $netImposable * $categoryRate->getValue() / 100;
    }

    /** Montant de la couverture maladie universelle du salarie, charge salarial de la periode de paie */
    public function amountCMUCampagneByDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $personal->getChargePeople()->count();
        $marie = $personal->getEtatCivil() === Status::MARIEE ? 1 : 0;
        $CMU = $categoryRate->getValue();
        return ($chargePeople * $CMU) + ($CMU * $marie) + $CMU;
    }

    /** Montant de la couverture maladie universelle du salarie, charge patronal de la periode de paie */
    public function amountCMUEmpCampagneByDeparture(): float|int
    {
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        return (int)$categoryRate->getValue();
    }

    /** Determiner le montant de la part patronal I.S locaux, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountISCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return $netImposable * $categoryRate?->getValue() / 100;
    }

    /** Montant du taux d'apprentissage, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountTACampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return $netImposable * $categoryRateFDFP_TA->getValue() / 100;
    }

    /** Montant de la FPC, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountFPCCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return $netImposable * $categoryRateFDFP_FPC->getValue() / 100;
    }

    /** Montant de la FPC complement annuel de la periode de paie
     * @throws Exception
     */
    public function amountFPCAnnuelCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateFDFP_FPC_VER = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return $netImposable * $categoryRateFDFP_FPC_VER->getValue() / 100;
    }

    /** Montant de la caisse de retraite du salarie, charge patronal de la periode de paie
     * @throws Exception
     */
    public function amountCRCampagneByDeparture(Departure $departure, Campagne $campagne): float|int
    {
        $majorationHeursSupp = $this->amountHeureSuppByCampagneAndDeparture($departure, $campagne);
        $primeAnciennete = $this->amountPrimeAncienneteCampagneByDeparture($departure);
        $brutImposable = $this->netImposableByNbDayOfPresence($departure);
        $congesPayes = null; // Ajouter après avoir calculer la fonction qui retourne l'allocation de conges payer.
        $netImposable = $brutImposable + $majorationHeursSupp + $primeAnciennete + $congesPayes;
        $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return $netImposable * $categoryRateRCNPS_CR->getValue() / 100;
    }

    /** Montant de la prestation familliale du salarie, charge patronal de la periode de paie */
    public function amountPFCampagneByDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $smig = (int)$personal->getSalary()->getSmig();
        $categoryRateRCNPS_PF = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF']);
        return $smig * $categoryRateRCNPS_PF->getValue() / 100;
    }

    /** Montant de l'accident de travail du salarie, charge patronal de la periode de paie */
    public function amountATCampagneByDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $smig = (int)$personal->getSalary()->getSmig();
        $categoryRateRCNPS_AT = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT']);
        return $smig * $categoryRateRCNPS_AT->getValue() / 100;
    }

    /** Montant de l'assurance sante part salariale et patronale de la periode de paie */
    public function amountAssuranceSanteByDeparture(Departure $departure): array
    {
        $personal = $departure->getPersonal();
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