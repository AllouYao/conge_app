<?php

namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'default')]
class DefaultScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {

        return (new Schedule())
            ->add(
                RecurringMessage::every('60 seconds', new UpdateOlderPersonal()),
                RecurringMessage::every('60 seconds', new UpdateGratification())
            );
    }
}