<?php

namespace App;

use App\Message;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class Scheduler implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        $from = new \DateTimeImmutable('01:00');
        $schedule->add(RecurringMessage::every('12 hours', new Message\SynchronizeConnectors(), $from));

        return $schedule;
    }
}
