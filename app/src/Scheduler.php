<?php

namespace App;

use App\Message;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
class Scheduler implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire(env: 'SCHEDULER_CONSOLIDATE_FREQUENCY')]
        private string $consolidateFrequency,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        $from = new \DateTimeImmutable('01:00');
        $schedule->add(RecurringMessage::every('12 hours', new Message\SynchronizeConnectors(), $from));

        $from = new \DateTimeImmutable('02:00');
        $schedule->add(RecurringMessage::every('24 hours', new Message\CleanData(), $from));

        $schedule->add(RecurringMessage::every('5 seconds', new Message\AmavisAutoRelease()));

        $schedule->add(RecurringMessage::every($this->consolidateFrequency, new Message\ConsolidateAmavisData()));

        return $schedule;
    }
}
