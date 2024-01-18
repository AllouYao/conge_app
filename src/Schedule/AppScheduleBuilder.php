<?php

namespace App\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class AppScheduleBuilder implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule
            ->timezone('UTC')
            ->environments('dev')
        ;

        $schedule->addCommand('app:anciennete')
            ->description('Send the weekly report to users.')
            ->everyTenMinutes()
        ;

    }
}