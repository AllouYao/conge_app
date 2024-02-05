<?php

namespace App\Service;

use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\CongeRepository;
use App\Repository\Paiement\PayrollRepository;
use App\Repository\Settings\PrimesRepository;
use App\Utils\Status;
use DatePeriod;
use DateTime;
use Exception;

class DepartServices
{
    private CongeService $congeService;
    private PayrollRepository $payrollRepository;
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;
    private CongeRepository $congeRepository;
    private PrimesRepository $primesRepository;
    private UtimePaiementService $utimePaiementService;

    public function __construct(
        CongeService         $congeService,
        PayrollRepository    $payrollRepository,
        CongeRepository      $congeRepository,
        PrimesRepository     $primesRepository,
        UtimePaiementService $utimePaiementService,

    )
    {
        $this->congeService = $congeService;
        $this->payrollRepository = $payrollRepository;
        $this->congeRepository = $congeRepository;
        $this->primesRepository = $primesRepository;
        $this->utimePaiementService = $utimePaiementService;
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

    /**
     * @throws Exception
     * Permet d'obtenir la valeur de l'indemnité de préavis du salarié
     */
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


    /** Permet d'obtenir l'indemnite de rupture de contrat de type CDD du salarié */
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


    /** Determiner le salaire global moyen */
    /** Premièrement Permet d'obtenir les élements de salaire */
    /** Dexièmement Obtenir la periode de reference */
    /** Troixièmement je vais determiner l'indemnite compensatrice de congés */
    public function getElements(Departure $departure): array
    {
        $anciennity = $this->getAncienneteByDepart($departure);
        $anciennityInYears = round($anciennity['anciennity_in_year'], 2);
        $personal = $departure->getPersonal();
        $basePeriode = round($this->utimePaiementService->getAmountSalaireBrutAndImposable($personal)['salaire_categoriel'], 2);
        $dateDepart = $departure->getDate();

        $genre = $personal->getGenre();
        $chargePeaple = $personal->getChargePeople();

        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $drCongeSupp1 = round($this->congeService->suppConger($genre, $chargePeaple, $dateDepart), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $drCongeSupp2 = round($this->congeService->echelonConge($anciennityInYears), 2);

        /** Permet d'obtenir la ligne du dernier congé éffectuer par le salarié  */
        $lastConges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $lastDateReturn = $lastConges?->getDateDernierRetour();
        if ($lastConges) {
            /** Salaire brut de la période */
            $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary1($personal, $lastDateReturn), 2);

        } else {
            /** Salaire brut de la période */
            $brutPeriode = round($this->payrollRepository->getPeriodiqueSalary2($personal, $dateDepart), 2);
        }

        /** Determiner la prime d'ancienneté */
        $primeAnciennity = round($this->utimePaiementService->getAmountAnciennete($personal), 2);

        /** Determiner les heurs supplémentaire */
        $heursSupplementaire = round($this->utimePaiementService->getAmountMajorationHeureSupp($personal), 2);

        return [
            'salaire_base' => $basePeriode,
            'last_conges' => $lastConges,
            'last_day_conges' => $lastConges?->getDateDernierRetour(),
            'salaire_periodique' => $brutPeriode,
            'jour_supp' => $drCongeSupp1 + $drCongeSupp2,
            'prime_anciennete' => $primeAnciennity,
            'heurs_supp' => $heursSupplementaire
        ];
    }

    public function getPeriodReference(mixed $start, mixed $end): ?int
    {
        $interval = new \DateInterval('P1M');
        $periode = new DatePeriod($start, $interval, $end);
        $mois = [];
        foreach ($periode as $date) {
            $mois[] = $date->format('F');
        }

        return count($mois);
    }

    public function getIndemniteCompensConges(Departure $departure): array
    {
        $element = $this->getElements($departure);
        $personal = $departure->getPersonal();
        // Obtenir le salaire moyen mensuel le denier conger dans le cas ou il en a
        $lastConges = $element['last_conges'];
        if ($lastConges) {
            /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
            $retourConge = $lastConges->getDateDernierRetour();
            $dateDepart = $departure->getDate();
            /** Obtenir le nombre de mois de presence que fait la période de présence */
            $monthPresence = $this->getPeriodReference($retourConge, $dateDepart);
            /** Convertir le mois de présence en nombre de jour calandaire */
            $jour_supp = $element['jour_supp'];
            $njCalandaire = $monthPresence * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE + $jour_supp;
            /** Quote-part de la prime de fin d'année de la periode de présence */
            $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
            $basePeriode = $element['salaire_base'];
            $quotePart = $basePeriode * $tauxGratif * ($monthPresence * 30 + $njCalandaire) / 360;
            $quotePartCorrespondent = round(($basePeriode * $tauxGratif * ($monthPresence + ($njCalandaire / 30))) / 12, 2);
            /** le salaire periodique comprends ici les element suivant : le brut, les heurs supp et la prime d'ancienneté de la periode  */
            $salairePeriodique = $element['salaire_periodique'];
            //  DETERMINONS MAINTENANT LE SALAIRE MOYEN MENSUEL (SMM) //
            $smm = ($salairePeriodique + $quotePart) / $monthPresence;
            //  DETERMINONS MAINTENANT L'INDEMNITE COMPENSATRICE DE CONGE //
            $indemniteConges = round(($smm * $njCalandaire) / 30, 2);
        } else {
            /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
            $dateEmbauche = $personal->getContract()->getDateEmbauche();
            $dateDepart = $departure->getDate();
            /** Obtenir le nombre de mois de presence que fait la période de présence */
            $monthPresence = $this->getPeriodReference($dateEmbauche, $dateDepart);
            /** Convertir le mois de présence en nombre de jour calandaire */
            $jour_supp = $element['jour_supp'];
            $njCalandaire = $monthPresence * self::JOUR_CONGE_OUVRABLE * self::JOUR_CONGE_CALANDAIRE + $jour_supp;
            /** Quote-part de la prime de fin d'année de la periode de présence */
            $tauxGratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
            $basePeriode = $element['salaire_base'];
            $quotePartAnnuel = $basePeriode * $tauxGratif;
            $quotePartCorrespondent = ($basePeriode * ($monthPresence / 12));

            $salairePeriodique = $element['salaire_periodique'];
            //  DETERMINONS MAINTENANT LE SALAIRE MOYEN MENSUEL (SMM) //
            $smm = ($salairePeriodique + $quotePartAnnuel) / 12;
            //  DETERMINONS MAINTENANT L'INDEMNITE COMPENSATRICE DE CONGE //
            $indemniteConges = round(($smm * $njCalandaire) / 30, 2);
        }


        return [
            'quote_part_correspondent' => $quotePartCorrespondent,
            'indemnite_compens_conges' => $indemniteConges,
            'nombre_jour_conges' => $njCalandaire
        ];
    }

    public function salaireGlobalMoyen(Departure $departure): float|int|null
    {
        $element = $this->getElements($departure);
        $lastConges = $element['last_conges'];
        $salairePeriodique = $element['salaire_periodique'];
        $indemniteCongesPayes = (double)$lastConges?->getAllocationConge();
        $gratification = (double)$lastConges?->getGratification();
        $indemniteCompensCongesAmount = $this->getIndemniteCompensConges($departure)['indemnite_compens_conges'];
        $quotePartCorrespondent = $this->getIndemniteCompensConges($departure)['quote_part_correspondent'];

        /** Nouveau salaire global des 12 dernier mois */
        $salaireGlobal = $salairePeriodique + $indemniteCongesPayes + $gratification + $indemniteCompensCongesAmount + $quotePartCorrespondent;
        /** Determiner maintenant le salaire global moyen du salarié */
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


    // REGIME FIXCAL APPLICABLE A L'INDEMNITE DE LICENCIEMENT //

    /** Permet de determiner le quotité de l'indemnité de licenciement non imposable et celle qui est imposable */
    public function getQuotityIndemniteLicenciement(Departure $departure): array
    {
        $indemniteLicenciement = $this->getIndemniteLicenciement($departure);
        $quotiteNonImposable = $indemniteLicenciement <= 50000.00 ? $indemniteLicenciement : round($indemniteLicenciement * (50 / 100), 2);
        $quotiteImposable = $indemniteLicenciement > 50000.00 ? round($indemniteLicenciement * (50 / 100), 2) : 0;
        return [
            'quotity_imposable_licenciement' => $quotiteImposable,
            'quotity_not_imposable_licenciement' => $quotiteNonImposable
        ];
    }

    /** Solde de présence à titre de l'année de départ
     * @throws Exception
     */
    public function getPresenceSolde(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $categorySalary = $personal->getCategorie()->getCategorySalarie()->getName();
        $ancienity = $this->getAncienneteByDepart($departure);
        $ancienityYears = round($ancienity['anciennity_in_year'], 2);
        /** Obtenir les mois précédent le jour du départ dépuis le premier mois de l'année */
        $dateDepart = $departure->getDate();
        $anneeDepart = $dateDepart->format('Y');
        $annee = (int)$anneeDepart;
        $firstDayOfYear = new DateTime("$annee-01");
        $interval = new \DateInterval('P1M');
        $periode = new \DatePeriod($firstDayOfYear, $interval, $dateDepart);
        $mois = [];
        foreach ($periode as $date) {
            $mois[] = $date->format('F');
        }
        /** Obtenir le nombre de mois de presence que fait la période */
        $monthPresence = count($mois);

        /** Obtenir le salaire moyen mensuel de l'annee de départ */
        $cumulSalaire = $this->payrollRepository->getCumulSalaries($personal, $firstDayOfYear, $dateDepart);

        /** Solde de preavis */
        $drPreavis = $this->getDrPreavisInMonth($ancienityYears, $categorySalary);
        $soldePreavis = $this->getIndmtCompensPreavis($departure);
        /** Solde de congé */
        $drConges = $this->getIndemniteCompensConges($departure)['nombre_jour_conges'];
        $soldeConges = $this->getIndemniteCompensConges($departure)['indemnite_compens_conges'];
        /** Gratification */
        $gratification = $this->getIndemniteCompensConges($departure)['quote_part_correspondent'];

        /** Periode total d'imposition (PTI) en jours */
        $PTI = ceil(($monthPresence + $drPreavis) * 30 + $drConges);

        // SOLDE DE PRESENCE //
        $soldePresence = $cumulSalaire + $soldePreavis + $soldeConges + $gratification;

        return [
            'periode_total_imposition' => $PTI,
            'solde_presence' => $soldePresence,
            'periode_presence' => $monthPresence
        ];
    }

    /** Remuneration moyen de l'année de depart
     * @throws Exception
     */
    public function getRemunerationMoyen(Departure $departure): float|int|null
    {
        $soldePreavis = $this->getIndmtCompensPreavis($departure);
        $soldePresence = $this->getPresenceSolde($departure)['solde_presence'];
        $soldeRemuneration = $soldePresence - $soldePreavis;
        $periodePresence = $this->getPresenceSolde($departure)['periode_presence'];
        $drConges = $this->getIndemniteCompensConges($departure)['nombre_jour_conges'];
        return round(($soldeRemuneration / ($periodePresence * 30 + $drConges)) * 30, 2);
    }

    /** Total du montant imposable ou RTI
     * @throws Exception
     */
    public function getTotalAmountImposable(Departure $departure): float|int|null
    {
        // Indemnite de licenciement imposable et solde de presence
        $soldePresence = $this->getPresenceSolde($departure)['solde_presence'];
        $quotiteImposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable_licenciement'];
        return $soldePresence + $quotiteImposable;
    }

    /** Etalement de la fraction taxable de l'indemnite de licenciement
     * @throws Exception
     */
    public function getEtalementIndemnite(Departure $departure): float|int|null
    {
        $periodTotalImposition = $this->getPresenceSolde($departure)['periode_total_imposition'];
        $quotiteImposable = $this->getQuotityIndemniteLicenciement($departure)['quotity_imposable_licenciement'];
        $remunerationMoyen = $this->getRemunerationMoyen($departure);
        $fractionTaxable = round(($quotiteImposable * 30) / $remunerationMoyen, 2);

        return ceil($periodTotalImposition + $fractionTaxable);

    }

    // REGULARISATION //

    /** Determiner le montant de l'impôts brut sur le total imposable, Departure
     * @throws Exception
     */
    public function calculerImpotBrutDeparture(Departure $departure): float|int
    {

        $netImposable = $this->getTotalAmountImposable($departure);
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

    /** Regularisation (Pour l'instant je suis pas convaincu je dois y travailler encore)
     * @throws Exception
     */
    public function getRegularisations(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $nbPart = $this->utimePaiementService->getNumberParts($personal); // nombre de part du salarie
        /** Extrapolation du salaire à l'année */
        $totalImposable = $this->getTotalAmountImposable($departure);
        $etalementIndemnite = $this->getEtalementIndemnite($departure);
        $extrapolation = round(($totalImposable * 360) / $etalementIndemnite, 2);
        /** Determination de l' ITS du montant imposable de depart */
        $impotBrut = round($this->calculerImpotBrutDeparture($departure), 2);
        $creditImpot = $this->calculateCreditImpotDeparture($departure);
        $amountIts = $impotBrut - $creditImpot;
        /** Determiner l' ITS du personal */
        $impotBrutPersonal = $this->utimePaiementService->calculerAmountImpotBrut($personal);
        $creditImpotPersonal = $this->utimePaiementService->calculateAmountCreditImpot($personal);
        $amountItsPersonal = round($impotBrutPersonal - $creditImpotPersonal);
        /** Reduction au prorata de la periode d'etalement */
        $reduction = round(($amountIts * $etalementIndemnite) / 360, 2);
        /** Difference à retenir pour regularisation */
        $periodePresence = $this->getPresenceSolde($departure)['periode_presence'];
        $difference = $reduction - ($amountItsPersonal * $periodePresence);

        //dd($nbPart, $extrapolation, $impotBrut, $creditImpot, $amountIts, $amountItsPersonal, $reduction, $difference);

        return [
            'difference' => $difference
        ];
    }

    /**
     * @throws Exception
     */
    public function calculeDroitsAndIndemnity(Departure $departure): void
    {
        $personal = $departure->getPersonal();
        $categoryName = $personal->getCategorie()->getCategorySalarie()->getName();
        $anciennityInYear = $this->getAncienneteByDepart($departure)['anciennity_in_year'];
        $reason = $departure->getReason();
        $salaireDue = (double)$this->payrollRepository->findLastPayroll(true)->getNetPayer();
        /** Element de conges */
        $conges = $this->getIndemniteCompensConges($departure);
        $gratification = $conges['quote_part_correspondent'];
        $allocationConge = $conges['indemnite_compens_conges'];
        /** Element de preavis */
        $drPreavis = $this->getDrPreavisInMonth($anciennityInYear, $categoryName);
        $indemnitePreavis = $this->getIndmtCompensPreavis($departure);

        /** Element de licenciement */
        $indemniteLicenciement = $this->getIndemniteLicenciement($departure);
        $departure
            ->setSalaryDue($salaireDue)
            ->setGratification($gratification)
            ->setCongeAmount($allocationConge);

        switch ($reason) {
            case $reason === Status::LICENCIEMENT_COLLECTIF:
            case $reason === Status::MALADIE:
            case $reason === Status::LICENCIEMENT_FAIT_EMPLOYEUR:
                $departure
                    ->setNoticeAmount($indemnitePreavis)
                    ->setDissmissalAmount($indemniteLicenciement);
                break;
            case $reason === Status::LICENCIEMENT_FAUTE_LOURDE:
            case $reason === Status::DEMISSION:
            case $reason === Status::ABANDON_DE_POST:
                break;
            case $reason === Status::RETRAITE:
            case $reason === Status::DECES:
                $departure
                    ->setDissmissalAmount($indemniteLicenciement);
                break;
        }
    }


}