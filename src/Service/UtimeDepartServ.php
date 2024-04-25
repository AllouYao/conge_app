<?php
declare(strict_types=1);

namespace App\Service;


use App\Entity\DossierPersonal\Departure;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;

class UtimeDepartServ
{
    public function __construct()
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

    /** Fonction qui permet d'obtenir le nombre de mois d'ancienneté du salarié depuis sont entrer jusqu'a son depart */
    public function getAnciennitySal(Departure $departure): int|float|null
    {
        $personal = $departure->getPersonal();
        $date_depart = $departure->getDate();
        $date_embauche = $personal->getContract()->getDateEmbauche();
        return ($date_depart->diff($date_embauche)->days / 360) * 12;
    }


}