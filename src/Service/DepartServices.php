<?php

namespace App\Service;

use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;

class DepartServices
{
    private CongeService $congeService;
    private PayrollRepository $payrollRepository;
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;
    private CongeRepository $congeRepository;
    private PrimesRepository $primesRepository;
    private UtimePaiementService $utimePaiementService;
    private CategoryChargeRepository $categoryChargeRepository;

    public function __construct(
        CongeService             $congeService,
        PayrollRepository        $payrollRepository,
        CongeRepository          $congeRepository,
        PrimesRepository         $primesRepository,
        UtimePaiementService     $utimePaiementService,
        CategoryChargeRepository $categoryChargeRepository

    )
    {
        $this->congeService = $congeService;
        $this->payrollRepository = $payrollRepository;
        $this->congeRepository = $congeRepository;
        $this->primesRepository = $primesRepository;
        $this->utimePaiementService = $utimePaiementService;
        $this->categoryChargeRepository = $categoryChargeRepository;
    }

    /** Permet d'obtenir l'ancienneté du salarie en fonction de son départ cela en jour, mois et année */
    public function getAncienneteByDepart(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $dateDepart = $departure->getDate();
        $dateEmbauche = $personal->getContract()->getDateEmbauche();
        $anciennityInDay = $dateDepart->diff($dateEmbauche)->days;
        $anciennityInMonth = $anciennityInDay / 12;
        $anciennityInYear = $anciennityInDay / 360;

        return [
            'anciennity_in_days' => round($anciennityInDay, 2),
            'anciennity_in_month' => round($anciennityInMonth, 2),
            'anciennity_in_year' => round($anciennityInYear, 2)
        ];
    }

    /** Element du personnel utilisé pour les départ */
    public function personalElementOfDeparture(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $genre = $personal->getGenre();
        $chargePeople = $personal->getChargePeople();
        $older = $personal->getOlder();
        $salaireBase = $this->utimePaiementService->getAmountSalaireBrutAndImposable($personal)['salaire_categoriel'];

        return [
            'personal_genre' => $genre,
            'personal_charge_peaple' => $chargePeople,
            'personal_ancienity' => $older,
            'salaire_base' => $salaireBase
        ];
    }

    /** Determiner le nombre de mois de présence ou periode de presence depuis le premier mois de l'annee*/
    public function getPeriodOfPresence($departure): float|int|null
    {
        /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
        $dateDepart = $departure->getDate();
        $anneeDepart = $dateDepart->format('Y');
        $annee = (int)$anneeDepart;
        $firstDayOfYear = new DateTime("$annee-01");
        $interval = new DateInterval('P1M');
        $periode = new DatePeriod($firstDayOfYear, $interval, $dateDepart);
        $mois = [];
        foreach ($periode as $date) {
            $mois[] = $date->format('F');
        }
        /** Obtenir le nombre de mois de presence que fait la période */
        return count($mois);

    }

    /** Determiner le nombre de jour de présence ou periode de presence depuis le premier mois de l'année */
    public function getPeriodOfPresenceInDay($departure): float|int|null
    {
        /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
        $dateDepart = $departure->getDate();
        $anneeDepart = $dateDepart->format('Y');
        $annee = (int)$anneeDepart;
        $firstDayOfYear = new DateTime("$annee-01");
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($firstDayOfYear, $interval, $dateDepart);
        $day = [];
        foreach ($periode as $date) {
            $day[] = $date;
        }
        /** Obtenir le nombre de mois de presence que fait la période */
        return count($day);

    }

    /** Determiner le nombre de jour de presence dans le mois courrant */
    public function getDayOfPresence(Departure $departure): float|int|null
    {
        $dateDepart = $departure->getDate();
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $firstDay = new DateTime("$year-$month-1");
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($firstDay, $interval, $dateDepart);
        $day = [];
        foreach ($periode as $date) {
            $day[] = $date;
        }
        return count($day);
    }

    /** Determiner le nombre de mois de présence ou periode de presence que */
    public function getPeriodReference(mixed $start, mixed $end): ?int
    {
        $interval = new DateInterval('P1M');
        $periode = new DatePeriod($start, $interval, $end);
        $mois = [];
        foreach ($periode as $date) {
            $mois[] = $date->format('F');
        }
        return count($mois);
    }

    /** Determiner l'indemnité compensatrice de congé */
    public function indemniteCompensatriceCgs(Departure $departure): array
    {
        /** Element of departure */
        $personalElement = $this->personalElementOfDeparture($departure);
        $personal = $departure->getPersonal();
        $ancienity = $this->getAncienneteByDepart($departure);
        $dateDepart = $departure->getDate();

        $retourDConge = null;
        $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        if ($lastConges) {
            /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
            $retourConge = $lastConges->getDateDernierRetour();
            $retourDConge = date_format($lastConges->getDateDernierRetour(), 'd/m/Y');
            /** Obtenir le nombre de mois de presence depuis le retour du conges jusqu'à la fin du mois précédent le mois de depart */
            $monthPresence = $this->getPeriodReference($retourConge, $dateDepart) - 1;
            /** Nombre de jour de présence éffectué pendant le mois actuel */
            $jrPdrOfPresence = $this->getDayOfPresence($departure);
            $totalMonthPresence = round($monthPresence + ($jrPdrOfPresence / 30), 2);

            /** Determiner le nombre de jour ouvrable */
            $dayOuvrable = ceil($totalMonthPresence * self::JOUR_CONGE_OUVRABLE);
            /** Determiner le nombre de jour calandaire */
            $dayCalandaire = ceil($dayOuvrable * self::JOUR_CONGE_CALANDAIRE);
            /** Jour de conges supplémentaire ou jour de majoration */
            $drJourSupp1 = $this->congeService->suppConger($personalElement['personal_genre'], $personalElement['personal_charge_peaple'], $dateDepart);
            $drJourSupp2 = $this->congeService->echelonConge((int)$ancienity['anciennity_in_year']);
            /** Jour total de congés */
            $drCongesTotal = $dayCalandaire + $drJourSupp1 + $drJourSupp2;

            /** Quote-part de la prime de fin d'année de la periode de présence */
            $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
            $basePeriode = $personalElement['salaire_base'];
            $newYearDayWork = $this->getPeriodOfPresenceInDay($departure);
            $quotePartCorrespondent = round($basePeriode * $tauxGratif * $newYearDayWork / 360, 2);

            /** Salaire brut de la période */
            $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary2($personal, $retourConge), 2);

        } else {
            /** Date d'embauche */
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
            /** Obtenir le nombre de mois de presence depuis le retour du conges jusqu'à la fin du mois précédent le mois de depart */
            $monthPresence = $this->getPeriodReference($dateEmbauche, $dateDepart) - 1;
            /** Nombre de jour de présence éffectué pendant le mois actuel */
            $jrPdrOfPresence = $this->getDayOfPresence($departure);
            $totalMonthPresence = round($monthPresence + ($jrPdrOfPresence / 30), 2);

            /** Determiner le nombre de jour ouvrable */
            $dayOuvrable = ceil($totalMonthPresence * self::JOUR_CONGE_OUVRABLE);
            /** Determiner le nombre de jour calandaire */
            $dayCalandaire = ceil($dayOuvrable * self::JOUR_CONGE_CALANDAIRE);
            /** Jour de conges supplémentaire ou jour de majoration */
            $drJourSupp1 = $this->congeService->suppConger($personalElement['personal_genre'], $personalElement['personal_charge_peaple'], $dateDepart);
            $drJourSupp2 = $this->congeService->echelonConge((int)$ancienity['anciennity_in_year']);
            /** Jour total de congés */
            $drCongesTotal = $dayCalandaire + $drJourSupp1 + $drJourSupp2;

            /** Quote-part de la prime de fin d'année de la periode de présence */
            $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
            $basePeriode = $personalElement['salaire_base'];
            $newYearDayWork = $this->getPeriodOfPresenceInDay($departure);
            $quotePartCorrespondent = round($basePeriode * $tauxGratif * $newYearDayWork / 360, 2);

            /** Salaire brut de la période */
            $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary1($personal, $dateDepart), 2);

        }
        /** Determiner le salaire moyen mensuel */
        $smm = round($brutPeriode / $totalMonthPresence, 2);
        /** Indemnite de congé */
        $indemniteConge = round(($smm * (self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE * $totalMonthPresence + $drJourSupp2 + $drJourSupp1)) / 30, 2);

        return [
            'duree_conges' => $drCongesTotal,
            'gratification_prorata' => $quotePartCorrespondent,
            'salaire_moyen_mensuel' => $smm,
            'indemnite_conge' => $indemniteConge,
            'date_dernier_conge' => $retourDConge
        ];
    }

    /** Determiner le salaire global moyen des 12 dernier mois */
    public function salaireGlobalMoyen(Departure $departure): float|int|null
    {
        $personal = $departure->getPersonal();
        $dateDepart = $departure->getDate();
        $lastConge = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $salaireBrutGlobal = $this->payrollRepository->getSalaireGlobal($personal, $dateDepart);
        $gratification = $personal->getSalary()?->getGratification();
        $lastAllocationConges = $lastConge?->getAllocationConge();
        $indemniteCompensatriceCgs = $this->indemniteCompensatriceCgs($departure)['indemnite_conge'];
        $quotePartOnEndYears = $this->indemniteCompensatriceCgs($departure)['gratification_prorata'];

        $salaireGlobal = $salaireBrutGlobal + $gratification + $lastAllocationConges + $indemniteCompensatriceCgs + $quotePartOnEndYears;

        return round($salaireGlobal / 12, 2);
    }

    /** Permet d'obtenir l'indemnite de licenciement du salarié et salaire global moyen */
    public function getIndemniteLicenciement(Departure $departure): float|int|null
    {
        /** Permet d'obtenir le salaire global moyen qui est la somme du salaire des 12 mois qui on précédé la date de depart */
        $salaireGlobalMoyen = $this->salaireGlobalMoyen($departure);
        $anciennity = $this->getAncienneteByDepart($departure);
        $anciennityYear = round($anciennity['anciennity_in_year']);
        /** Determiner la quotite du salaire global moyen */
        $qt1 = ((30 / 100) * $salaireGlobalMoyen);
        $qt2 = ((35 / 100) * $salaireGlobalMoyen);
        $qt3 = ((40 / 100) * $salaireGlobalMoyen);
        $indemniteLicenciement = null;

        switch ($anciennityYear) {
            case $anciennityYear < 1:
                $indemniteLicenciement = 0;
                break;
            case $anciennityYear <= 5:
                $indemniteLicenciement = round($anciennityYear * $qt1, 2);
                break;
            case $anciennityYear >= 6 && $anciennityYear <= 10:
                $indemniteLicenciement = round(5 * $qt1 + ($anciennityYear - 5) * $qt2, 2);
                break;
            case $anciennityYear > 10:
                $indemniteLicenciement = round(5 * $qt1 + 5 * $qt2 + ($anciennityYear - 10) * $qt3, 2);
                break;
        }
        return $indemniteLicenciement;
    }

    /** Determiner le salaire de présence */
    public function salarieOfPresence(Departure $departure): float|int|null
    {
        $dateDepart = $departure->getDate();
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $firstDay = new DateTime("$year-$month-1");
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($firstDay, $interval, $dateDepart);
        $day = [];
        foreach ($periode as $date) {
            $day[] = $date;
        }
        $dayPresence = count($day);
        $netPayer = (double)$this->payrollRepository->getAmountNetPayer($departure->getPersonal());
        return round(($netPayer / 30) * $dayPresence, 2);
    }

    /** Permet d'obtenir la durée de préavis du salarié en fonction de son ancienneté et de sa catégory */
    public function getDrPreavisInMonth(mixed $anciennity, mixed $categorySalary): ?int
    {
        $drPreavis = null;
        if ($categorySalary == Status::OUVRIER_EMPLOYE || $categorySalary == Status::CHAUFFEUR) switch ($anciennity) {
            case $anciennity <= 6:
                $drPreavis = 1;
                break;
            case $anciennity > 6 && $anciennity <= 11:
                $drPreavis = 2;
                break;
            case $anciennity > 11 && $anciennity <= 16:
                $drPreavis = 3;
                break;
            case $anciennity > 16:
                $drPreavis = 4;
                break;
            default:
                $drPreavis = 0;
                break;
        }
        if ($categorySalary == Status::AGENT_DE_MAITRISE || $categorySalary == Status::CADRE) switch ($anciennity) {
            case $anciennity <= 16:
                $drPreavis = 3;
                break;
            case $anciennity > 16:
                $drPreavis = 4;
                break;
            default:
                $drPreavis = 0;
                break;
        }

        return $drPreavis;
    }

    /** Permet d'obtenir la valeur de l'indemnité de préavis du salarié */
    public function getIndmtCompensPreavis(Departure $departure): float|int|null
    {
        $personal = $departure->getPersonal();
        $categoryName = $personal->getCategorie()->getCategorySalarie()->getName();
        $anciennityInYear = $this->getAncienneteByDepart($departure)['anciennity_in_year'];
        $drPreavis = $this->getDrPreavisInMonth($anciennityInYear, $categoryName);
        $primeAnciennity = $this->utimePaiementService->getAmountAnciennete($personal);
        $heursSupp = $this->utimePaiementService->getAmountMajorationHeureSupp($personal);
        $salaireBrut = $personal->getSalary()->getBrutAmount() - $personal->getSalary()->getAmountAventage();

        /** Determiner le plafond de l'indemnite theorique exonere */
        $transport = $this->utimePaiementService->getPrimeTransportLegal();
        $primeNonJuridique = round($personal->getSalary()->getTotalPrimeJuridique(), 2);

        /** Rémumération total hors avantage en nature  */
        $remuneration = round($salaireBrut + $heursSupp + $primeAnciennity, 2);

        /** indemnite theorique exonere */
        $theoriqueExonere = round((10 / 100) * $remuneration - $transport, 2);

        /** indemnite reel exonere */
        $reelExonere = min($primeNonJuridique, $theoriqueExonere);

        /** le salaire brut imposable de préavis */
        $brutImposable = round($remuneration - $reelExonere - $transport, 2);

        /** Determiner l'agravation de l'indemnite */
        $dateDepart = $departure->getDate();
        $indemniteSupplementaire = null;
        $conges = $this->congeRepository->getLastCongeByID($personal->getId(), true);
        if ($conges) {
            $dateRetourConge = $conges->getDateDernierRetour();
            $dateDepartConge = $conges->getDateDepart();
            $nextDate = $dateRetourConge;
            $lastDate = $dateDepartConge;
            $nextFifteenDays = [];
            $lastFifteenDays = [];
            for ($i = 1; $i <= 15; $i++) {
                $dateN = clone $nextDate;
                $dateL = clone $lastDate;
                $dateN->modify("+$i days");
                $dateL->modify("-$i days");
                $nextFifteenDays[] = $dateN;
                $lastFifteenDays[] = $dateL;
            }
            if ($nextFifteenDays) {
                if ($dateDepart > $nextFifteenDays[0] && $dateDepart <= $nextFifteenDays[14]) {
                    $indemniteSupplementaire = round($brutImposable * 2, 2);
                }
            } elseif ($lastFifteenDays) {
                if ($dateDepart > $lastFifteenDays[14] && $dateDepart <= $lastFifteenDays[0]) {
                    $indemniteSupplementaire = round($brutImposable * 2, 2);
                }
            }
        }
        /** Determination de l'indemnite de préavis */
        return round(($brutImposable * $drPreavis) + $indemniteSupplementaire, 2);
    }

    /** Determiner les frais funéraire en cas de décès du salarié */
    public function getFraisFuneraire(Departure $departure): float|int|null
    {
        $anciennityInYear = $this->getAncienneteByDepart($departure)['anciennity_in_year'];
        $salaireCategoriel = $this->personalElementOfDeparture($departure)['salaire_base'];
        if ($anciennityInYear >= 1 && $anciennityInYear <= 5) {
            $fraisFuneraire = round($salaireCategoriel * 3, 2);
        } elseif ($anciennityInYear > 5 && $anciennityInYear <= 10) {
            $fraisFuneraire = round($salaireCategoriel * 4, 2);
        } else {
            $fraisFuneraire = round($salaireCategoriel * 6, 2);
        }

        return $fraisFuneraire;
    }

    // REGIME FIXCAL APPLICABLE A L'INDEMNITE DE LICENCIEMENT //

    /** Permet de determiner le quotité de l'indemnité de licenciement non imposable et celle qui est imposable */
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
            'quotity_imposable_licenciement' => $quotiteImposable,
            'quotity_not_imposable_licenciement' => $quotiteNonImposable
        ];
    }

    /** Permet de determiner le montant total des droits et indemnites imposable du depart */
    public function getTotalIndemniteImposable(Departure $departure): float|int|null
    {
        $reason = $departure->getReason();
        $personal = $departure->getPersonal();
        /** Solde de présence */
        if ($reason === Status::ABANDON_DE_POST || $reason === Status::LICENCIEMENT_FAUTE_LOURDE) {
            $soldePresence = $this->salarieOfPresence($departure);
        } else {
            $soldePresence = (double)$this->payrollRepository->getAmountNetPayer($personal);
        }
        /** Solde de préavis */
        $soldePreavis = $this->getIndmtCompensPreavis($departure);
        /** Solde de congé */
        $soldeConges = $this->indemniteCompensatriceCgs($departure)['indemnite_conge'];
        /** Gratification */
        $gratification = $this->indemniteCompensatriceCgs($departure)['gratification_prorata'];
        /** Indemnite de licenciement */
        $indemniteLicenciement = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable_licenciement'];
        switch ($reason) {
            case $reason === Status::DEMISSION:
                $totalIndemniteImposable = $soldePresence + $gratification + $soldeConges;
                break;
            case $reason === Status::ABANDON_DE_POST || $reason === Status::LICENCIEMENT_FAUTE_LOURDE:
                $totalIndemniteImposable = $soldePresence + $soldeConges + $gratification;
                break;
            case $reason === Status::LICENCIEMENT_COLLECTIF || $reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR || $reason === Status::MALADIE || $reason === Status::RETRAITE:
                $totalIndemniteImposable = $soldePresence + $soldePreavis + $soldeConges + $gratification + $indemniteLicenciement;
                break;
            case $reason === Status::DECES:
                $totalIndemniteImposable = $indemniteLicenciement;
                break;
            default:
                $totalIndemniteImposable = 0;
        }

        return round($totalIndemniteImposable, 2);

    }

    // CALCULE DES DROITS LEGAUX POUR LE DEPART DU SALARIE //
    public function calculeDroitsAndIndemnity(Departure $departure): void
    {
        $personal = $departure->getPersonal();
        $categoryName = $personal->getCategorie()->getCategorySalarie()->getName();
        $anciennityInYear = $this->getAncienneteByDepart($departure)['anciennity_in_year'];
        $reason = $departure->getReason();

        /** Element de conges */
        $indemniteConges = $this->indemniteCompensatriceCgs($departure)['indemnite_conge'];
        $gratification = $this->indemniteCompensatriceCgs($departure)['gratification_prorata'];

        /** Element de preavis */
        $drPreavis = $this->getDrPreavisInMonth($anciennityInYear, $categoryName);
        $indemnitePreavis = $this->getIndmtCompensPreavis($departure);

        /** Element de licenciement */
        $indemniteLicenciement = $this->getIndemniteLicenciement($departure);
        $indemniteLcmtImposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable_licenciement'];
        $indemniteLcmtNoImposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_not_imposable_licenciement'];

        /** Frais funéraire */
        $fraisFuneraire = $this->getFraisFuneraire($departure);

        /** salaire de presence */
        $salaireDue = (double)$this->payrollRepository->getAmountNetPayer($personal);
        $salaireDueProrata = $this->salarieOfPresence($departure);

        /** Total des droits et indemnites imposable */
        $totalIndemniteImposable = $this->getTotalIndemniteImposable($departure);

        switch ($reason) {
            case $reason === Status::LICENCIEMENT_FAUTE_LOURDE || $reason === Status::ABANDON_DE_POST || $reason === Status::DEMISSION:
                if ($reason === Status::ABANDON_DE_POST || $reason === Status::LICENCIEMENT_FAUTE_LOURDE) {
                    $salairePresence = $salaireDueProrata;
                } else {
                    $salairePresence = $salaireDue;
                }
                $departure
                    ->setSalaryDue($salairePresence)
                    ->setGratification($gratification)
                    ->setCongeAmount($indemniteConges)
                    ->setNoticeAmount(null)
                    ->setDissmissalAmount(null)
                    ->setAmountLcmtImposable(null)
                    ->setAmountLcmtNoImposable(null)
                    ->setFraisFuneraire(null)
                    ->setTotalIndemniteImposable($totalIndemniteImposable);

                break;
            case $reason === Status::LICENCIEMENT_COLLECTIF || $reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR || $reason === Status::MALADIE || $reason === Status::RETRAITE:
                $departure
                    ->setSalaryDue($salaireDue)
                    ->setGratification($gratification)
                    ->setCongeAmount($indemniteConges)
                    ->setNoticeAmount($indemnitePreavis)
                    ->setDissmissalAmount($indemniteLicenciement)
                    ->setAmountLcmtImposable($indemniteLcmtImposable)
                    ->setAmountLcmtNoImposable($indemniteLcmtNoImposable)
                    ->setFraisFuneraire(null)
                    ->setTotalIndemniteImposable($totalIndemniteImposable);
                break;
            case $reason === Status::DECES:
                $departure
                    ->setSalaryDue(null)
                    ->setGratification(null)
                    ->setCongeAmount(null)
                    ->setNoticeAmount(null)
                    ->setDissmissalAmount($indemniteLicenciement)
                    ->setAmountLcmtImposable($indemniteLcmtImposable)
                    ->setAmountLcmtNoImposable($indemniteLcmtNoImposable)
                    ->setFraisFuneraire($fraisFuneraire)
                    ->setTotalIndemniteImposable($totalIndemniteImposable);
                break;
        }
    }

    // REGIME FIXCAL APPLICABLE A L'INDEMNITE DE LICENCIEMENT SUITE//

    /** Determiner le montant de l'impôts brut sur le total imposable, Departure
     */
    public function calculerImpotBrutDeparture(Departure $departure): float|int
    {

        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
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
        return round($impotBrut, 2);
    }

    /** Determiner le montant du crédit d'impôt à déduit sur l'impôts brut, Departure */
    function calculateCreditImpotDeparture(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $nbrePart = $this->utimePaiementService->getNumberParts($personal);
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

    // LES CHARGE SALARIALES APPLIQUEES A L'INDEMNITE DE DEPART //

    /** Montant de l'ITS du depart */
    public function getAmountITS(Departure $departure): float|int|null
    {
        $impotBrut = $this->calculerImpotBrutDeparture($departure);
        $creditImpot = $this->calculateCreditImpotDeparture($departure);

        return $impotBrut - $creditImpot;
    }

    /** Montant de la Cnps de 6,3 % */
    public function getAmountCNPS(Departure $departure): float
    {

        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        if ($netImposable > 1647314) {
            $netImposable = 1647314;
        }
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return round($netImposable * $categoryRate->getValue() / 100, 2);
    }

    // LES CHARGE PATRONALES APPLIQUEES A L'INDEMNITE DE DEPART //

    /** Determiner le montant de la part patronal I.S locaux, de 1,20 % */
    public function getAmountIS(Departure $departure): float|int
    {
        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return round($netImposable * $categoryRate?->getValue() / 100, 2);
    }

    /** Determiner le montant de la prestation familliale du salarie, de 5,75 % */
    public function getAmountRCNPS_PF(Departure $departure): float|int
    {
        $smig = $departure->getPersonal()->getSalary()->getSmig();
        $categoryRateRCNPS_PF = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF']);
        return round($smig * $categoryRateRCNPS_PF->getValue() / 100, 2);
    }

    /** Determiner le montant de l'accident de travail du salarie, de 5,00 % */
    public function getAmountRCNPS_AT(Departure $departure): float|int
    {
        $smig = $departure->getPersonal()->getSalary()->getSmig();
        $categoryRateRCNPS_AT = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT']);
        return round($smig * $categoryRateRCNPS_AT->getValue() / 100, 2);
    }

    /** Determiner le montant de la caisse de retraite du salarie, de 7,70 % */
    public function getAmountRCNPS_CR(Departure $departure): float|int
    {
        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        $categoryRateRCNPS_CR = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return round($netImposable * $categoryRateRCNPS_CR->getValue() / 100, 2);
    }

    /** Determiner le montant du taux d'apprentissage, charge patronal */
    public function getAmountTA(Departure $departure): float|int
    {
        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        $categoryRateFDFP_TA = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return round($netImposable * $categoryRateFDFP_TA->getValue() / 100, 2);
    }

    /** Determiner le montant de la FPC, charge patronal */
    public function getAmountFPC(Departure $departure): float|int
    {
        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        $categoryRateFDFP_FPC = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return round($netImposable * $categoryRateFDFP_FPC->getValue() / 100, 2);
    }

    /** Determiner le montant de la FPC complement annuel */
    public function getAmountFPCAnnuel(Departure $departure): float|int
    {
        //$netImposable = $this->getTotalIndemniteImposable($departure);
        $netImposable = (double)$departure->getTotalIndemniteImposable();
        $categoryRateFDFP_FPC_VER = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return round($netImposable * $categoryRateFDFP_FPC_VER->getValue() / 100, 2);
    }

    /** Determiner le montant de la couverture maladie universelle du salarie, charge salarial */
    public function getAmountCMU(Departure $departure): float|int
    {
        $categoryRate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $chargePeople = $departure->getPersonal()->getChargePeople()->count();
        $marie = $departure->getPersonal()->getEtatCivil() === Status::MARIEE ? 1 : 0;
        $CMU = $categoryRate->getValue();
        return ($chargePeople * $CMU) + ($CMU * $marie) + $CMU;
    }

}