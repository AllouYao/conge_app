Pour activer mon server Mysql:

- docker compose up -d
- #C9971C
- sudo systemctl restart cron.service
- crontab -e
- symfony console cron:list
- symfony console cron:run
- symfony console cron:start
- php bin/console schedule:list
- php bin/console schedule:list --detail

        /** Determiner la prime de fin d'année ou gratification */
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;
        $firstDay = new DateTime("$year-$month-1");
        $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
        $currentDay = clone $firstDay;
        $t = [];
        while ($currentDay <= $lastDay)

        dd($firstDay, $lastDay);
- dd([
  'raison du depart' => $reason,
  'salaire du ou de presence' => $salaireDue,
  'indemnite de congé' => $indemniteConges,
  'gratification au prorata' => $gratification,
  'durée de préavis' => $drPreavis,
  'indemnite de préavis' => $indemnitePreavis,
  'indemnite de licenciement' => $indemniteLicenciement,
  'salaire de base' => $this->personalElementOfDeparture($departure)['salaire_base'],
  'frais funéraire' => $fraisFuneraire,
  'departure' => $departure
  ]);
