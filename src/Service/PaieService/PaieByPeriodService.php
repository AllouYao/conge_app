<?php

namespace App\Service\PaieService;

use App\Entity\Paiement\Campagne;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class PaieByPeriodService
{
    /** Obtenir le nombre de jour de présence depuis la date d'embauche actuel jusqu'au dernier jour du mois
     * @throws Exception
     */
    public function NbDayOfPresenceByCurrentMonth(Campagne $campagne): float|int|null
    {
        /** Obtenir les jours précédent le jour du départ dépuis le premier jours du mois de licenciement de l'année */
        $dateDepart = $campagne->getDateDebut();
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
}