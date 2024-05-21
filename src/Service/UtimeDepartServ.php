<?php

namespace App\Service;


use App\Contract\DepartureInterface;
use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Service\CasExeptionel\PaieOutService;
use App\Service\Personal\PrimeService;
use App\Utils\Status;
use Exception;

class UtimeDepartServ implements DepartureInterface
{
    const NR_JOUR_TRAVAILLER = 30;


    public function __construct(
        private readonly PaieOutService            $outService,
        private readonly PrimeService              $primeService,
        private readonly ChargePersonalsRepository $chargePersonalsRepository,
        private readonly ChargeEmployeurRepository $chargeEmployeurRepository,
        private readonly CongeRepository           $congeRepository,
        private readonly PayrollRepository         $payrollRepository
    )
    {
    }

    /**
     * Fonction pui permet d'obtenir le salaire de presence en fonction du nombre de jour travailler par le salarié avant sont départ
     * @throws Exception
     */
    public function getSalairePresent(Departure $departure): float|null
    {
        $day_of_presence = $this->outService->getSalaires($departure)['day_of_presence'];

        /** Ajouter les éléments qui constitue le salaire imposable du salarié */
        $salary = $departure->getPersonal()->getSalary();
        $salaire = $this->outService->getSalaires($departure);
        $base_salaire = ceil((double)$salaire['salaire_categoriel']);
        $sursalaire = ceil((double)$salary->getSursalaire() * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $majorationHeursSupp = round($this->outService->getMajorations($departure));

        /** Ajouter toutes les primes possible  */
        $prime_fonctions = ceil($this->primeService->getPrimeFonction($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_logements = ceil($this->primeService->getPrimeLogement($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $indemnite_fonctions = ceil($this->primeService->getIndemniteFonction($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $indemnite_logements = ceil($this->primeService->getIndemniteLogement($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_transport_legal = ceil($this->primeService->getPrimeTransportLegal() * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_transport_imposable = ceil(((double)($salary->getPrimeTransport() * $day_of_presence / self::NR_JOUR_TRAVAILLER) - $prime_transport_legal));
        $prime_paniers = ceil($this->primeService->getPrimePanier($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_salissures = ceil($this->primeService->getPrimeSalissure($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_tenue_travails = ceil($this->primeService->getPrimeTT($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_outillages = ceil($this->primeService->getPrimeOutil($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $prime_rendement = ceil($this->primeService->getPrimeRendement($departure->getPersonal()) * $day_of_presence / self::NR_JOUR_TRAVAILLER);

        /** Avantage en nature non imposable */
        $avantage_non_imposable = round((double)$salary->getAvantage()?->getTotalAvantage() * $day_of_presence / self::NR_JOUR_TRAVAILLER);
        $avantage_nature_imposable = round(((double)($salary?->getAmountAventage() * $day_of_presence / self::NR_JOUR_TRAVAILLER) - $avantage_non_imposable));

        /** Ajouter les charges du salarié ( retenues fiscales et sociales) */
        $charge_personal = $this->chargePersonalsRepository->findOneBy(['personal' => $departure->getPersonal(), 'departure' => $departure]);
        $nombre_part = round($charge_personal?->getNumPart(), 1);
        $salary_its = ceil($charge_personal?->getAmountIts());
        $salary_cnps = ceil($charge_personal?->getAmountCNPS());
        $salary_cmu = ceil($charge_personal?->getAmountCMU());
        $assurance_salariale = $this->outService->getAssurance($departure)['assurance_salariale'];
        $amount_charg_fiscal_personal = $salary_its;
        $amount_charg_social_personal = $salary_cnps + $salary_cmu + $assurance_salariale;
        $charge_salarie = ceil($amount_charg_fiscal_personal + $amount_charg_social_personal);

        /** Ajouter les charges de l'employeur (retenues fiscales et sociales) */
        $charge_employeur = $this->chargeEmployeurRepository->findOneBy(['personal' => $departure->getPersonal(), 'departure' => $departure]);
        $employeur_is = round($charge_employeur?->getAmountIS());
        $employeur_fpc = round($charge_employeur?->getAmountFPC());
        $employeur_fpc_annuel = round($charge_employeur?->getAmountAnnuelFPC());
        $employeur_ta = round($charge_employeur?->getAmountTA());
        $employeur_cmu = round($charge_employeur?->getAmountCMU());
        $employeur_cr = round($charge_employeur?->getAmountCR());
        $employeur_pf = round($charge_employeur?->getAmountPF());
        $employeur_at = round($charge_employeur?->getAmountAT());
        $assurance_patronale = $this->outService->getAssurance($departure)['assurance_patronale'];
        $amount_charg_fiscal_patronale = $employeur_is + $employeur_fpc + $employeur_fpc_annuel + $employeur_ta;
        $amount_charg_social_patronale = $employeur_cmu + $employeur_cr + $employeur_at + $employeur_pf + $assurance_patronale;
        $charge_patronal = round($amount_charg_fiscal_patronale + $amount_charg_social_patronale);

        /** Ajouter le salaire brut qui constitue l'ensemble des élements de salaire imposable et non imposable */
        $salaire_brut = $base_salaire + $sursalaire + $majorationHeursSupp + $prime_fonctions + $prime_logements
            + $indemnite_fonctions + $indemnite_logements + $prime_transport_imposable + $avantage_nature_imposable
            + $prime_transport_legal + $avantage_non_imposable;

        /** Ajouter le net imposable qui constitue l'ensemble des élements de salaire imposable uniquement */
        $net_imposable = $base_salaire + $sursalaire + $majorationHeursSupp + $prime_fonctions + $prime_logements
            + $indemnite_fonctions + $indemnite_logements + $prime_transport_imposable + $avantage_nature_imposable;

        /** Ajouter le net à payer, total retenue, indemnité de transport et assurance santé du personnel */
        return ceil(($net_imposable + $prime_transport_legal + $avantage_non_imposable + $prime_tenue_travails + $prime_salissures + $prime_outillages + $prime_rendement + $prime_paniers) - ($charge_salarie));
    }

    /** Fonction qui permet d'obtenir la durée de préavis du salarié */
    public function getTimePreavis(mixed $anciennity, mixed $category_salary): ?int
    {
        $time_preavis = null;
        if ($category_salary == Status::OUVRIER_EMPLOYE || $category_salary == Status::CHAUFFEUR) $time_preavis = match ($anciennity) {
            $anciennity <= 6 => 1,
            $anciennity > 6 && $anciennity <= 11 => 2,
            $anciennity > 11 && $anciennity <= 16 => 3,
            $anciennity > 16 => 4,
            default => 0,
        };

        if ($category_salary == Status::AGENT_DE_MAITRISE || $category_salary == Status::CADRE) $time_preavis = match ($anciennity) {
            $anciennity <= 16 => 3,
            $anciennity > 16 => 4,
            default => 0,
        };

        return $time_preavis;
    }

    /**
     * Fonction qui permet d'obtenir l'indemnite de preavis du salarie qui est sur le départ
     * @throws Exception
     */
    public function getIndemnityPreavis(Departure $departure): float|null
    {
        $personal = $departure->getPersonal();
        $category_name = $personal->getCategorie()->getCategorySalarie()->getName();
        $anciennity = (int)($this->outService->getAnciennitySal($departure) / 12);
        $time_preavis = $this->getTimePreavis($anciennity, $category_name);
        $prime_ancienity = $this->outService->getPrimeAncien($departure);
        $majoration = $this->outService->getMajorations($departure);
        $salaire_brut = $this->outService->getSalaires($departure)['brut_amount'] - $personal->getSalary()->getAmountAventage();

        /** Determiner le plafond de l'indemnite theorique exonere */
        $transport = $this->primeService->getPrimeTransportLegal();
        $prime_non_juridique = round($personal->getSalary()->getTotalPrimeJuridique(), 2);

        /** Rémumération total hors avantage en nature  */
        $remuneration = round($salaire_brut + $majoration + $prime_ancienity, 2);

        /** indemnite theorique exonere */
        $theorique_exonere = round(((10 / 100) * $remuneration) - $transport, 2);

        /** indemnite reel exonere */
        $reel_exonere = min($prime_non_juridique, $theorique_exonere);

        /** le salaire brut imposable de préavis */
        $brut_imposable = round($remuneration - ($reel_exonere + $transport), 2);

        /** Determiner l'agravation de l'indemnite */
        $date_depart = $departure->getDate();
        $indemnite_supplementaire = 0;
        $conges = $this->congeRepository->getLastCongeByID($personal->getId(), true);
        if ($conges) {
            $nextDate = $conges->getDateDernierRetour();
            $lastDate = $conges->getDateDepart();
            $nextFifteenDays = [];
            $lastFifteenDays = [];
            for ($i = 1; $i <= 15; $i++) {
                $dateN = clone $nextDate;
                $dateL = clone $lastDate;
                $dateN->modify("+$i days");
                $dateL->modify("-$i days");
                $next_fifteen_days[] = $dateN;
                $last_fifteen_days[] = $dateL;
            }
            if ($nextFifteenDays) {
                if ($date_depart > $next_fifteen_days[0] && $date_depart <= $next_fifteen_days[14]) {
                    $indemnite_supplementaire = round($brut_imposable * 2, 2);
                }
            } elseif ($lastFifteenDays) {
                if ($date_depart > $last_fifteen_days[14] && $date_depart <= $last_fifteen_days[0]) {
                    $indemnite_supplementaire = round($brut_imposable * 2, 2);
                }
            }
        }
        /** Determination de l'indemnite de préavis */
        return round(($brut_imposable * $time_preavis) + $indemnite_supplementaire, 2);
    }

    /** Fonction qui permet d'obtenir l'indemnite compensatrice de congé du salarié qui est sur le départ */
    public function getIndemniteConges(Departure $departure): array
    {
        if ($departure->getDateRetourConge()) {
            $indemnite_conges = $this->outService->getLastConges($departure);
        } else {
            $indemnite_conges = $this->outService->getFirstConges($departure);
        }
        return $indemnite_conges;
    }

    /** Fonction qui permet d'obtenir les frais funéraire en cas de licenciement pour cause de décès du salarié */
    public function getFraisFuneraire(Departure $departure): float|null
    {
        $ancienity = (int)($this->outService->getAnciennitySal($departure) / 12);
        $salaire_categoriel = $this->outService->getSalaires($departure)['salaire_categoriel'];
        if ($ancienity >= 1 && $salaire_categoriel <= 5) {
            $frais_funeraire = round($salaire_categoriel * 3, 2);
        } elseif ($ancienity > 5 && $salaire_categoriel <= 10) {
            $frais_funeraire = round($salaire_categoriel * 4, 2);
        } else {
            $frais_funeraire = round($salaire_categoriel * 6, 2);
        }
        return $frais_funeraire;
    }

    /** Fonction qui permet d'obtenir le salaire globale moyen mensuel du salarié sur les douze dernier mois qu'il à travailler au sein de l'entreprise */
    public function getSalaireGlobalMoyen(Departure $departure): float|int|null
    {
        $lastConge = $this->congeRepository->getLastCongeByID($departure->getPersonal()->getId(), false);
        $gratification = $departure->getPersonal()->getSalary()?->getGratification();
        $last_allocation = $lastConge?->getAllocationConge();
        $indemnite_compensatrice_cgs = $this->getIndemniteConges($departure)['indemnite_conges'];
        $gratification_prorata = $this->getIndemniteConges($departure)['gratification'];

        $prime_anciennete = $this->outService->getPrimeAncien($departure);
        $brut_imposable_amount = $this->outService->getSalaires($departure)['brut_imposable_amount'];

        $payrolls = $departure->getPersonal()->getPayrolls()->count();
        if ($payrolls < 12) {
            $brut_globale = ($brut_imposable_amount + $prime_anciennete) * 12;
        } else {
            $brut_globale = $this->payrollRepository->getSalaireGlobal($departure->getPersonal(), $departure->getDate());
        }

        $salaire_global = $gratification + $last_allocation + $indemnite_compensatrice_cgs + $gratification_prorata + $brut_globale;

        return round($salaire_global / 12);
    }

    /** Fonction qui permet d'obtenir l'indemnite de licenciement du salarié qui est sur le départ */
    public function getIndemniteLicenciement(Departure $departure): float|int|null
    {
        /** Permet d'obtenir le salaire global moyen qui est la somme du salaire des 12 mois qui on précédé la date de depart */
        $salaireGlobalMoyen = $this->getSalaireGlobalMoyen($departure);
        $anciennity = round($this->outService->getAnciennitySal($departure) / 12);
        /** Determiner la quotite du salaire global moyen */
        $qt1 = ((30 / 100) * $salaireGlobalMoyen);
        $qt2 = ((35 / 100) * $salaireGlobalMoyen);
        $qt3 = ((40 / 100) * $salaireGlobalMoyen);
        $indemniteLicenciement = null;

        switch ($anciennity) {
            case $anciennity < 1:
                $indemniteLicenciement = 0;
                break;
            case $anciennity <= 5:
                $indemniteLicenciement = round($anciennity * $qt1, 2);
                break;
            case $anciennity >= 6 && $anciennity <= 10:
                $indemniteLicenciement = round(5 * $qt1 + ($anciennity - 5) * $qt2, 2);
                break;
            case $anciennity > 10:
                $indemniteLicenciement = round(5 * $qt1 + 5 * $qt2 + ($anciennity - 10) * $qt3, 2);
                break;
        }
        return $indemniteLicenciement;
    }

    // REGIME FIXCAL APPLICABLE A L'INDEMNITE DE LICENCIEMENT //
    public function getQuotityIndemniteLicenciement(Departure $departure): array
    {
        $reason = $departure->getReason();
        $indemniteLicenciement = $this->getIndemniteLicenciement($departure);
        $quotiteNonImposable = $indemniteLicenciement <= 50000.00 ? $indemniteLicenciement : round($indemniteLicenciement * (50 / 100), 2);
        $quotiteImposable = $indemniteLicenciement - $quotiteNonImposable;
        if ($reason === Status::RETRAITE || $reason === Status::DECES) {
            $quotiteImposable = $indemniteLicenciement;
            $quotiteNonImposable = null;
        }
        return [
            'quotity_imposable' => $quotiteImposable,
            'quotity_non_imposable' => $quotiteNonImposable
        ];
    }

    /**
     * Fonction qui permet de determiner le montant total de l'indemnité imposable du salarié qui est sur le départ
     * @throws Exception
     */
    public function getTotalIndemniteImposable(Departure $departure): float|int|null
    {
        $reason = $departure->getReason();
        /** Solde de présence */
        $soldePresence = $this->getSalairePresent($departure);
        /** Solde de préavis */
        $soldePreavis = $this->getIndemnityPreavis($departure);
        /** Solde de congé */
        $soldeConges = round($this->getIndemniteConges($departure)['indemnite_conges']);
        /** Gratification */
        $gratification = $this->getIndemniteConges($departure)['gratification'];
        /** Indemnite de licenciement */
        $indemniteLicenciement = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable'];
        switch ($reason) {
            case $reason === Status::DEMISSION;
                $totalIndemniteImposable = $soldePresence + $gratification + $soldeConges;
                break;
            case $reason === Status::LICENCIEMENT_FAUTE_LOURDE:
                $totalIndemniteImposable = $gratification + $soldeConges + $soldePresence;
                break;
            case $reason === Status::LICENCIEMENT_FAUTE_SIMPLE:
                $totalIndemniteImposable = $soldePresence + $soldePreavis + $soldeConges + $gratification + $indemniteLicenciement;
                break;
            case $reason === Status::RETRAITE:
                $totalIndemniteImposable = $soldePresence + $soldeConges + $gratification + $indemniteLicenciement + $soldePreavis;
                break;
            case $reason === Status::DECES:
                $totalIndemniteImposable = $indemniteLicenciement + $soldePresence + $gratification + $soldeConges;
                break;
            default:
                $totalIndemniteImposable = 0;
        }

        return round($totalIndemniteImposable, 2);

    }

    /**
     * @throws Exception
     */
    public function departurePersonalCharge(Departure $departure): void
    {
        $indemnite_imposable = $this->getTotalIndemniteImposable($departure);
        $parts = $this->outService->getNombrePart($departure);
        $impot_brut = $this->outService->getImpotBrutByIndemnite($indemnite_imposable);
        $credit_impot = $this->outService->getCreditImpotByPart($parts);
        $impot_net = $this->outService->getImpotNet($impot_brut, $credit_impot);
        if ($indemnite_imposable <= 75000 || $impot_net < 0) {
            $impot_net = 0;
        }
        $amount_cnps = $this->outService->getAmountCNPS($indemnite_imposable);
        $amount_cmu = $this->outService->getCmu($departure);
        $total_charge = $amount_cnps + $amount_cmu + $impot_net;
        $net_payer = $indemnite_imposable - $total_charge;
        $departure
            ->setNbPart($parts)
            ->setImpotBrut($impot_brut)
            ->setCreditImpot($credit_impot)
            ->setImpotNet($impot_net)
            ->setAmountCnps($amount_cnps)
            ->setAmountCmu($amount_cmu)
            ->setTotatChargePersonal($total_charge)
            ->setNetPayer($net_payer);
    }

    /**
     * @throws Exception
     */
    public function departureEmployeurCharge(Departure $departure): void
    {
        $indemnite_imposable = $this->getTotalIndemniteImposable($departure);
        $montant_is = $this->outService->getAmountIS($indemnite_imposable);
        $montant_cr = $this->outService->getAmountRCNPS_CR($indemnite_imposable);
        $montant_pf = $this->outService->getPrestFamily($departure);
        $montant_at = $this->outService->getAccidentWorks($departure);
        $montant_ta = $this->outService->getAmountTA($indemnite_imposable);
        $montant_fpc = $this->outService->getAmountFPC($indemnite_imposable);
        $montant_fpc_year = $this->outService->getAmountFPCAnnuel($indemnite_imposable);
        $montant_cmu = $this->outService->getCmuEmployer();
        $total_rate_cnps = $montant_cr + $montant_pf + $montant_at;
        $total_charge = $montant_is + $montant_fpc + $montant_fpc_year + $montant_ta + $total_rate_cnps + $montant_cmu;

        $departure
            ->setAmountIs($montant_is)
            ->setAmountCr($montant_cr)
            ->setAmountPf($montant_pf)
            ->setAmountAt($montant_at)
            ->setAmountTa($montant_ta)
            ->setAmountFpc($montant_fpc)
            ->setAmountFpcYear($montant_fpc_year)
            ->setAmountCmuE($montant_cmu)
            ->setTotalChargeEmployer($total_charge);
    }

    /**
     * @throws Exception
     */
    public function droitIndemnityByDeparture(Departure $departure): void
    {
        $reason = $departure->getReason();
        $periode_conges = $this->getIndemniteConges($departure)['periode_reference'];
        $conges_ouvrable = $this->getIndemniteConges($departure)['jour_ouvrable'];
        $indemnite_conges = $this->getIndemniteConges($departure)['indemnite_conges'];
        $gratification = $this->getIndemniteConges($departure)['gratification'];
        $gratification_correspondant = $this->getIndemniteConges($departure)['gratification_correspondent'];
        $salaire_moyen = $this->getIndemniteConges($departure)['cumul_salaire'];
        $indemnite_preavis = $this->getIndemnityPreavis($departure);
        $salaire_globale_moyen = $this->getSalaireGlobalMoyen($departure);
        $indemnite_licenciement = $this->getIndemniteLicenciement($departure);
        $quote_imposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable'];
        $quote_non_imposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_non_imposable'];
        $frais_funeraire = $this->getFraisFuneraire($departure);
        $salaire_presence = $this->getSalairePresent($departure);
        $total_indemnite_imposable = $this->getTotalIndemniteImposable($departure);
        $day_presence = $this->outService->getDaysPresence($departure);

        if ($reason === Status::LICENCIEMENT_FAUTE_LOURDE || $reason === Status::DEMISSION) {
            $departure
                ->setDayOfPresence($day_presence)
                ->setSalaryDue($salaire_presence)
                ->setGratification($gratification)
                ->setCumulSalaire($salaire_moyen)
                ->setCongeAmount($indemnite_conges)
                ->setPeriodeReferences($periode_conges)
                ->setCongesOuvrable($conges_ouvrable)
                ->setTotalIndemniteImposable($total_indemnite_imposable);
        }

        if ($reason === Status::LICENCIEMENT_FAUTE_SIMPLE || $reason === Status::RETRAITE) {
            $departure
                ->setDayOfPresence($day_presence)
                ->setSalaryDue($salaire_presence)
                ->setGratification($gratification)
                ->setGratificationCorresp($gratification_correspondant)
                ->setCumulSalaire($salaire_moyen)
                ->setCongeAmount($indemnite_conges)
                ->setPeriodeReferences($periode_conges)
                ->setCongesOuvrable($conges_ouvrable)
                ->setNoticeAmount($indemnite_preavis)
                ->setGlobalMoyen($salaire_globale_moyen)
                ->setDissmissalAmount($indemnite_licenciement)
                ->setAmountLcmtImposable($quote_imposable)
                ->setAmountLcmtNoImposable($quote_non_imposable)
                ->setTotalIndemniteImposable($total_indemnite_imposable);
        }

        if ($reason === Status::DECES) {
            $departure
                ->setDayOfPresence($day_presence)
                ->setSalaryDue($salaire_presence)
                ->setGratification($gratification)
                ->setGratificationCorresp($gratification_correspondant)
                ->setCumulSalaire($salaire_moyen)
                ->setCongeAmount($indemnite_conges)
                ->setPeriodeReferences($periode_conges)
                ->setCongesOuvrable($conges_ouvrable)
                ->setNoticeAmount($indemnite_preavis)
                ->setGlobalMoyen($salaire_globale_moyen)
                ->setDissmissalAmount($indemnite_licenciement)
                ->setAmountLcmtImposable($quote_imposable)
                ->setAmountLcmtNoImposable($quote_non_imposable)
                ->setFraisFuneraire($frais_funeraire)
                ->setTotalIndemniteImposable($total_indemnite_imposable);
        }
    }

    public function getIndemniteRuptureCdd(Departure $departure): float|int|null
    {
        $personal = $departure->getPersonal();
        $contrat = $personal->getContract();
        $typeContrat = $contrat->getTypeContrat();
        $indemniteFinContract = $gratification = $conge = null;
        if ($typeContrat === Status::CDD) {
            $embauche = $contrat->getDateEmbauche();
            $finEmbauche = $departure->getDate();
            $dureeContract = $finEmbauche->diff($embauche)->days / 30;
            $element = $this->utimePaiementService->getAmountSalaireBrutAndImposable($personal);
            $brut = $element['brut_amount'];
            $totalRemuneration = $brut * $dureeContract;
            $indemniteFinContract = $totalRemuneration * 3 / 100;
            /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
            $interval = new \DateInterval('P1M');
            $periode = new \DatePeriod($embauche, $interval, $finEmbauche);
            $mois = [];
            foreach ($periode as $date) {
                $mois[] = $date->format('F');
            }
            /** Obtenir le nombre de mois de presence que fait la période et la gratification */
            $monthPresence = count($mois);
            $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
            $basePeriode = round($this->utimePaiementService->getAmountSalaireBrutAndImposable($personal)['salaire_categoriel'], 2);
            $gratification = round(($basePeriode * $tauxGratif * ($monthPresence * 30) / 360), 2);

            /** Obtenir l'allocation conges */
            $jourConge = ceil($monthPresence * 2.2 * 1.25);
            $cumulPeriode = $this->payrollRepository->getCumulSalaries($personal, $embauche, $finEmbauche);
            $salaireMoyen = ($cumulPeriode + $gratification) / $jourConge;
            $conge = round(($salaireMoyen / 30) * $jourConge, 2);

        }
        return round($indemniteFinContract + $gratification + $conge, 2);
    }
}