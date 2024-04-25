<?php

namespace App\Service\CasExeptionel;

use App\Entity\DossierPersonal\Departure;
use App\Entity\Paiement\Campagne;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Service\AbsenceService;
use App\Service\UtimeDepartServ;
use App\Utils\Status;
use DateTime;
use Exception;

class PaieOutService
{
    const NR_JOUR_TRAVAILLER = 30;

    public function __construct(
        private readonly AbsenceService                    $absenceService,
        private readonly HeureSupRepository                $heureSupRepository,
        private readonly CategoryChargeRepository          $categoryChargeRepository,
        private readonly RetenueForfetaireRepository       $forfetaireRepository,
        private readonly DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository,
        private readonly AbsenceRepository                 $absenceRepository,
        private readonly UtimeDepartServ                   $utimeDepartServ
    )
    {
    }

    /** Fonction pour determiner les élément de salaire du salariés au cours du mois de départ */
    public function getSalaires(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $day_of_presence = $this->utimeDepartServ->getDaysPresence($departure);


        $date_depart = $departure->getDate();
        $month = (int)$date_depart->format('m');
        $years = (int)$date_depart->format('Y');

        $salaire_base = (int)$personal->getCategorie()?->getAmount();
        $absences = $this->absenceRepository->getAbsenceByMonth($personal, $month, $years);

        if ($absences) {
            $jours = 0;
            foreach ($absences as $absence) {
                $nb_absence = $this->absenceService->countDays($absence);
                $jours += $nb_absence;
            }
            $new_day_presence = $day_of_presence - $jours;
            $sal_categoriel = ceil($salaire_base * $new_day_presence / self::NR_JOUR_TRAVAILLER);
            $salaire_brut = ceil((int)$personal->getSalary()?->getBrutAmount() * $new_day_presence / self::NR_JOUR_TRAVAILLER);
            $brut_imposable = ceil((int)$personal->getSalary()?->getBrutImposable() * $new_day_presence / self::NR_JOUR_TRAVAILLER);
        } else {
            $new_day_presence = $day_of_presence;
            $sal_categoriel = $salaire_base * $new_day_presence / self::NR_JOUR_TRAVAILLER;
            $salaire_brut = $personal->getSalary()?->getBrutAmount() * $new_day_presence / self::NR_JOUR_TRAVAILLER;
            $brut_imposable = $personal->getSalary()?->getBrutImposable() * $new_day_presence / self::NR_JOUR_TRAVAILLER;
        }

        return [
            'day_of_presence' => $new_day_presence,
            'salaire_categoriel' => ceil($sal_categoriel),
            'brut_amount' => ceil($salaire_brut),
            'brut_imposable_amount' => ceil($brut_imposable)
        ];
    }

    /** Fonction pour determiner le montant de la majoration des heures supp du salarié au cours du mois de depart */
    public function getMajorations(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $date_depart = $departure->getDate();
        $month = (int)$date_depart->format('m');
        $years = (int)$date_depart->format('Y');
        $first_day = new DateTime("$years-$month-1");
        $last_day = new DateTime(date('Y-m-t', mktime(0, 0, 0, $month + 1, 0, $years)));
        $majoration = 0;
        $heures_supple = $this->heureSupRepository->getHeureSupByPeriode($personal, $first_day, $last_day);
        foreach ($heures_supple as $heure) {
            $majoration += $heure?->getAmount();
        }
        return $majoration;
    }

    /** Fonction pour determiner le montant de l'ancienneté du salarié qui est sur le départ */
    public function getPrimeAncien(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $salaire_base = $this->getSalaires($departure)['salaire_categoriel'];
        $anciennete = (int)$this->utimeDepartServ->getAnciennitySal($departure) / 12;
        if ($anciennete >= 2 && $anciennete < 3) {
            $prime_anciennity = $salaire_base * 2 / 100;
        } elseif ($anciennete >= 3 && $anciennete <= 25) {
            $prime_anciennity = ($salaire_base * $anciennete) / 100;
        } elseif ($anciennete >= 26) {
            $prime_anciennity = ($salaire_base * 25) / 100;
        } else {
            $prime_anciennity = 0;
        }
        return $prime_anciennity;
    }

    /** Fonction pour determiner le nombre de part du salarié qui est sur le départ */
    public function getNombrePart(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $nb_part = [
            'CELIBATAIRE' => 1,
            'MARIE' => 2,
            'VEUF' => 1,
            'DIVORCE' => 1
        ];
        $charge_people = $personal->getChargePeople()->count();

        if ($personal->getEtatCivil() === 'CELIBATAIRE' || $personal->getEtatCivil() === 'DIVORCE') {
            return match (true) {
                $charge_people == 1 => 2,
                $charge_people == 2 => 2.5,
                $charge_people == 3 => 3,
                $charge_people == 4 => 3.5,
                $charge_people == 5 => 4,
                $charge_people == 6 => 4.5,
                $charge_people > 6 => 5,
                default => 1,
            };
        } elseif ($personal->getEtatCivil() === 'MARIE') {
            return match (true) {
                $charge_people == 1 => 2.5,
                $charge_people == 2 => 3,
                $charge_people == 3 => 3.5,
                $charge_people == 4 => 4,
                $charge_people == 5 => 4.5,
                $charge_people >= 6 => 5,
                default => 2,
            };
        } elseif ($personal->getEtatCivil() === 'VEUF') {
            return match (true) {
                $charge_people == 1 => 2.5,
                $charge_people == 2 => 3,
                $charge_people == 3 => 3.5,
                $charge_people == 4 => 4,
                $charge_people == 5 => 4.5,
                $charge_people >= 6 => 5,
                default => 1,
            };
        }
        return $nb_part[$personal->getEtatCivil()];
    }

    /** Fonction pour determiner l'impôt brut du salarié qui est sur le départ */
    public function getImpotBrut(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $tranches_imposit = [
            ['min' => 0, 'limite' => 75000, 'taux' => 0],
            ['min' => 75001, 'limite' => 240000, 'taux' => 0.16],
            ['min' => 240001, 'limite' => 800000, 'taux' => 0.21],
            ['min' => 800001, 'limite' => 2400000, 'taux' => 0.24],
            ['min' => 2400001, 'limite' => 8000000, 'taux' => 0.28],
            ['min' => 8000001, 'limite' => PHP_INT_MAX, 'taux' => 0.32],
        ];

        $impot_brut = 0;

        foreach ($tranches_imposit as $tranche) {
            $limite_min = $tranche['min'];
            $limite_max = $tranche['limite'];
            $taux_imposit = $tranche['taux'];
            if ($net_imposable > $limite_min && $net_imposable >= $limite_max) {
                $montant_imposee = ($limite_max - $limite_min) * $taux_imposit;
                $impot_brut += $montant_imposee;
            } else if ($net_imposable > $limite_min) {
                $montant_imposee = ($net_imposable - $limite_min) * $taux_imposit;
                $impot_brut += $montant_imposee;
                break;
            }
        }
        return $impot_brut;
    }

    /** Fonction pour determiner le credit d'impôt du salarié qui est sur le départ */
    function getCreditImpot(Departure $departure): float|int
    {
        $nb_part = $this->getNombrePart($departure);

        $credit_impot = null;
        switch ($nb_part) {
            case 1;
                $credit_impot = 0;
                break;
            case 1.5;
                $credit_impot = 5500;
                break;
            case 2;
                $credit_impot = 11000;
                break;
            case 2.5;
                $credit_impot = 16500;
                break;
            case 3;
                $credit_impot = 22000;
                break;
            case 3.5;
                $credit_impot = 27500;
                break;
            case 4;
                $credit_impot = 33000;
                break;
            case 4.5;
                $credit_impot = 38500;
                break;
            case 5;
                $credit_impot = 44000;
                break;
        }
        return $credit_impot;
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