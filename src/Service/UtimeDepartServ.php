<?php

namespace App\Service;


use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\DossierPersonal\OldCongeRepository;
use App\Repository\Impots\ChargeEmployeurRepository;
use App\Repository\Impots\ChargePersonalsRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\CasExeptionel\PaieOutService;
use App\Service\Personal\PrimeService;
use App\Utils\Status;
use DateInterval;
use DatePeriod;
use Exception;

class UtimeDepartServ
{
    const NR_JOUR_TRAVAILLER = 30;


    public function __construct(
        private readonly PaieOutService            $outService,
        private readonly PrimeService              $primeService,
        private readonly ChargePersonalsRepository $chargePersonalsRepository,
        private readonly ChargeEmployeurRepository $chargeEmployeurRepository,
        private readonly CongeRepository           $congeRepository,
        private readonly OldCongeRepository        $oldCongeRepository,
        private readonly PayrollRepository         $payrollRepository,
    )
    {
    }

    /** Fonction qui permet d'obtenir le nombre de mois de travail effectuée par le salarié au cours de l'année de depart */
    public function getMonthPresence(mixed $start, mixed $end): int|null
    {
        $interval = new DateInterval('P1M');
        $periode = new DatePeriod($start, $interval, $end);
        $month = [];
        foreach ($periode as $period) {
            $month[] = $period->format('F');
        }
        return count($month) - 1;
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

    public function getIndemniteConges(Departure $departure)
    {
        $first_conges = $this->outService->getFirstConges($departure);
        dd($first_conges);
    }



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


    // REGIME FIXCAL APPLICABLE A L'INDEMNITE DE LICENCIEMENT //


}