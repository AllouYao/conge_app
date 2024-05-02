<?php

namespace App\Service\CasExeptionel;

use App\Entity\DossierPersonal\Departure;
use App\Repository\DossierPersonal\AbsenceRepository;
use App\Repository\DossierPersonal\DetailRetenueForfetaireRepository;
use App\Repository\DossierPersonal\HeureSupRepository;
use App\Repository\DossierPersonal\RetenueForfetaireRepository;
use App\Repository\Impots\CategoryChargeRepository;
use App\Repository\Settings\PrimesRepository;
use App\Service\AbsenceService;
use App\Service\CongeService;
use App\Utils\Status;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class PaieOutService
{
    const NR_JOUR_TRAVAILLER = 30;
    const JOUR_CONGE_OUVRABLE = 2.2;
    const JOUR_CONGE_CALANDAIRE = 1.25;

    public function __construct(
        private readonly AbsenceService                    $absenceService,
        private readonly HeureSupRepository                $heureSupRepository,
        private readonly CategoryChargeRepository          $categoryChargeRepository,
        private readonly RetenueForfetaireRepository       $forfetaireRepository,
        private readonly DetailRetenueForfetaireRepository $detailRetenueForfetaireRepository,
        private readonly AbsenceRepository                 $absenceRepository,
        private readonly CongeService                      $congeService,
        private readonly PrimesRepository                  $primesRepository


    )
    {
    }

    /** Fonction qui permet d'obtenir le nombre de jour de présence effectuée par le salarié au cours du mois de depart */
    public function getDaysPresence(Departure $departure): int|null
    {
        $date_depart = $departure->getDate();
        $today = Carbon::today();
        $month = $date_depart->format('m');
        $years = $today->year;
        $first_day = new DateTime("$years-$month-1");
        $interval = new DateInterval('P1D');
        $periode = new DatePeriod($first_day, $interval, $date_depart);
        $table_days = [];
        foreach ($periode as $period) {
            $table_days[] = $period;
        }
        return count($table_days);
    }

    /** Fonction qui permet d'obtenir le nombre de mois d'ancienneté du salarié depuis sont entrer jusqu'a son depart */
    public function getAnciennitySal(Departure $departure): int|float|null
    {
        $personal = $departure->getPersonal();
        $date_depart = $departure->getDate();
        $date_embauche = $personal->getContract()->getDateEmbauche();
        return ($date_depart->diff($date_embauche)->days / 360) * 12;
    }


    /** Fonction pour determiner les élément de salaire du salariés au cours du mois de départ */
    public function getSalaires(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $day_of_presence = $this->getDaysPresence($departure);


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

    /** Fonction pour determiner le montant de la majoration des heures supp du salarié au cours du mois de depart
     * @throws Exception
     */
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
        $salaire_base = $this->getSalaires($departure)['salaire_categoriel'];
        $anciennete = (int)$this->getAnciennitySal($departure) / 12;
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

    /** Fonction pour determiner l'impôt brut du salarié qui est sur le départ
     * @throws Exception
     */
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

    /** Fonction pour determiner le montant de la caisse de retraite national dû par le salarié qui est sur le depart (CNPS)
     * @throws Exception
     */
    public function getCnps(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        if ($net_imposable > 1647314) {
            $net_imposable = 1647314;
        }
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'CNPS']);
        return $net_imposable * $category_rate->getValue() / 100;
    }

    /** Fonction pour determiner le montant de couverture maladie universelle du salarié qui est sur le depart (CMU) */
    public function getCmu(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        // Je recupere le nombre d'enfant à charge
        $charge_people = 0;
        foreach ($personal->getChargePeople() as $charge_person) {
            if ($charge_person->isIsCmu() === true && $charge_person->getNumCmu() != null) {
                $charge_people++;
            }
        }
        $marie = $personal->getEtatCivil() === Status::MARIEE;
        $marie_cmu = 0;
        if ($marie && $personal->isIsCmu() === true && $personal->getNumCmu() != null) {
            $marie_cmu = 1;
        }
        $cmu_value = $category_rate->getValue();
        return ($charge_people * $cmu_value) + ($cmu_value * $marie_cmu) + $cmu_value;
    }

    // Determinons maintenant les charge que l'employeur doit versés pour ce salarié //

    /** Fonction pour determiner le montant de couverture maladie universelle à verser par l'employeur (CMU) */
    public function getCmuEmployer(): float|int
    {
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'CMU']);
        return (int)$category_rate->getValue();
    }

    /**
     * Fonction pour determiner l'impôt sur salaire à verser par l'employeur (IS)
     * @throws Exception
     */
    public function getIS(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'IS']);
        return $net_imposable * $category_rate?->getValue() / 100;
    }

    /**
     * Fonction pour determiner le fdfp d'apprentissage à verser par l'employeur (FDFP_TA)
     * @throws Exception
     */
    public function getTauxLearns(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_TA']);
        return $net_imposable * $category_rate?->getValue() / 100;
    }

    /**
     * Fonction pour determiner le fpc à verser par l'employeur (FDFP_FPC)
     * @throws Exception
     */
    public function getFpc(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC']);
        return $net_imposable * $category_rate?->getValue() / 100;
    }

    /**
     * Fonction pour determiner le fpc annuel à verser par l'employeur (FDFP_FPC_VER)
     * @throws Exception
     */
    public function getFpcAnnuel(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'FDFP_FPC_VER']);
        return $net_imposable * $category_rate?->getValue() / 100;
    }

    /**
     * Fonction pour determiner le caisse de retraite à verser pas l'employeur (RCNPS_CR)
     * @throws Exception
     */
    public function getCnpsRetraite(Departure $departure): float|int
    {
        $majoration = $this->getMajorations($departure);
        $prime_anciennete = $this->getPrimeAncien($departure);
        $brut_imposable = $this->getSalaires($departure)['brut_imposable_amount'];
        $net_imposable = $majoration + $prime_anciennete + $brut_imposable;
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_CR']);
        return $net_imposable * $category_rate?->getValue() / 100;
    }

    /** Fonction pour determiner la prestation familiale à verser par l'employeur (RCNPS_PF) */
    public function getPrestFamily(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $smigs = (int)$personal->getSalary()->getSmig();
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_PF']);
        return $smigs * $category_rate?->getValue() / 100;
    }

    /** Fonction pour determiner l'accident de travail à verser par l'employeur (RCNPS_AT) */
    public function getAccidentWorks(Departure $departure): float|int
    {
        $personal = $departure->getPersonal();
        $smigs = (int)$personal->getSalary()->getSmig();
        $category_rate = $this->categoryChargeRepository->findOneBy(['codification' => 'RCNPS_AT']);
        return $smigs * $category_rate?->getValue() / 100;
    }

    /** Fonction pour determiner l'assurance à verser par l'employeur et par le salarié qui est sur le depart */
    public function getAssurance(Departure $departure): array
    {
        $personal = $departure->getPersonal();
        $assure_classic = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_CLASSIC]);
        $assure_family = $this->forfetaireRepository->findOneBy(['code' => Status::ASSURANCE_FAMILLE]);
        $amount_classic = $this->detailRetenueForfetaireRepository->findRetenueForfetaire($personal, $assure_classic);
        $amount_family = $this->detailRetenueForfetaireRepository->findRetenueForfetaire($personal, $assure_family);
        $salariale = 0;
        $patronale = 0;

        if ($amount_classic) {
            $salariale = $amount_classic->getAmount();
            $patronale = $amount_classic->getAmountEmp();
        } elseif ($amount_family) {
            $salariale = $amount_family->getAmount();
            $patronale = $amount_family->getAmountEmp();
        }

        return [
            'assurance_salariale' => $salariale,
            'assurance_patronale' => $patronale
        ];
    }

    /** Determiner le nombre de mois de présence ou periode de presence du salarier en fonction des paramètres */
    public function getPeriodeConges(mixed $start, mixed $end): ?int
    {
        $interval = new DateInterval('P1M');
        $periode = new DatePeriod($start, $interval, $end);
        $mois = [];
        foreach ($periode as $date) {
            $mois[] = $date->format('F');
        }
        return count($mois) - 1;
    }


    /** Determiner le nombre de jour de présence ou periode de presence depuis le premier jours de l'année jusqu'a la date de depart */
    public function getPeriodePresence($departure): float|int|null
    {
        /** Obtenir les jours précédent le jour du départ dépuis le premier jours de l'année */
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
        /** Obtenir le nombre de jours de presence que fait la période */
        return ceil(count($day) / 1.25);
    }


    public function getLastConges(Departure $departure): void
    {
        /** Information du salarié sujet au congés */
        $personal = $departure->getPersonal();
        $anciennity = (int)($this->outService->getAnciennitySal($departure) / 12);
        $date_depart = $departure->getDate();
        $genre = $personal->getGenre();
        $charge_peaple = $personal->getChargePeople();

        $date_dernier_retour = null;
        $nb_work_month_in_year = null;

        $last_conges = $this->congeRepository->getLastCongeByID($personal->getId(), false);
        $historique_conge = $this->oldCongeRepository->findOneByPerso($personal->getId());
        $date_last_conges = $last_conges ? $last_conges->getDateDernierRetour() : $historique_conge->getDateRetour();
        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $dr_conge_supp_1 = round($this->congeService->suppConger($genre, $charge_peaple, $date_depart), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $dr_conge_supp_2 = round($this->congeService->echelonConge($anciennity), 2);
        /** Obtenir le nombre de mois de presence depuis le retour du conges jusqu'à la fin du mois précédent le mois de depart */
        $month_works = $this->outService->getPeriodeConges($date_last_conges, $date_depart);
        /** Nombre de jour de présence éffectué pendant le mois actuel, jour ouvrable */
        $day_works_current_month = $this->outService->getDaysPresence($departure);
        /** Nombre total de mois travailler par le salaire depuis sont dernier retour de congés */
        $total_works_month = round($month_works + ($day_works_current_month * 1.25 / 30));
        /** Determiner nombre de jour ouvrable de congés */
        $day_ouvrable_cgs = ceil($total_works_month * self::JOUR_CONGE_OUVRABLE);
        /** Determiner nombre de jour calandaire de congés */
        $day_calandre_cgs = ceil($day_ouvrable_cgs * self::JOUR_CONGE_CALANDAIRE);
        /** Nombre de jours total de congés */
        $total_day_conges = $day_ouvrable_cgs + $dr_conge_supp_1 + $dr_conge_supp_2;
        /** Quote-part de la prime de fin d'année de la periode de présence */
        $taux_gratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
        $base_periode = $this->outService->getSalaires($departure)['salaire_categoriel'];
        /** nombre de jour ouvrable de présence dans l'annee actuel depuis le debut de l'année */
        $day_presence = $this->outService->getPeriodePresence($departure);
        $quotePartCorrespondent = round($base_periode * $taux_gratif * $day_presence * 1.25 / 360);

        /** Determiner le salaire brut de la periode */
        if ($last_conges) {
            $sal_brut_periode = round((int)$this->payrollRepository->getPeriodiqueSalary2($personal, $date_last_conges) / 12);
        } else {
            $sal_brut_periode = (int)$historique_conge->getSalaryAverage();
        }
        /** Determiner le salaire moyen mensuel (SMM) des 12 dernier mois travailler par le salarie */
        $somme = round($sal_brut_periode, 2);
    }

    public function getFirstConges(Departure $departure): void
    {
        /** Donnee sur le salarié */
        $personal = $departure->getPersonal();
        $date_embauche = $personal->getContract()->getDateEmbauche();
        $genre = $personal->getGenre();
        $charge_peaple = $personal->getChargePeople();
        $anciennity = (int)($this->getAnciennitySal($departure) / 12);
        $date_depart = $departure->getDate();

        /** Obtenir le nombre de mois de presence depuis la date d'embauche jusqu'à la fin du mois précédent le mois de depart */
        $month_works = $this->getPeriodeConges($date_embauche, $date_depart);
        /** Nombre de jour de présence éffectué pendant le mois actuel */
        $day_actual_month = $this->getDaysPresence($departure);
        /** Total mois de presence effectuer pas le salariés depuis sa date d'embauche jusqu'au jour du départ */
        $total_month_works = (int)($month_works + ($day_actual_month * 1.25 / 30));
        /** Determiner le nombre de jour ouvrable */
        $day_ouvrable = ceil($total_month_works * self::JOUR_CONGE_OUVRABLE);
        /** Determiner le nombre de jour calandaire */
        $day_calandaire = ceil($day_ouvrable * self::JOUR_CONGE_CALANDAIRE);
        /** Jour de congé supplémentaire en fonction du sex et des enfant à charge */
        $dr_conge_supp_1 = round($this->congeService->suppConger($genre, $charge_peaple, $date_depart), 2);
        /** Jour supplémentaire de congé en fonction de l'ancienneté du salarié */
        $dr_conge_supp_2 = round($this->congeService->echelonConge($anciennity), 2);
        /** Nombre total de jour de conges */
        $total_day_conges = $day_calandaire + $dr_conge_supp_1 + $dr_conge_supp_2;
        /** Quote-part de la prime de fin d'année de la periode de présence */
        $taux_gratif = (int)$this->primesRepository->findOneBy(['code' => Status::GRATIFICATION])->getTaux() / 100;
        $categoriel_periode = $this->getSalaires($departure)['salaire_categoriel'];
        /** Nombre de jour ouvrable effectuer pas le salarié au cours de l'année en vigueure */
        $day_ouvrable_vigueure = $this->getPeriodePresence($departure);
        /** Gratification au prorata du nombre de jour ouvrable effectuer pendant l'année en vigueure */

        dd($taux_gratif, $categoriel_periode, $day_ouvrable_vigueure);
    }

}