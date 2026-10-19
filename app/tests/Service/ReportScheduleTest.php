<?php

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Service\ReportSchedule;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

class ReportScheduleTest extends TestCase
{
    public function testNewAccountAfterTheSendingTimeWaitsForTheNextOccurrence(): void
    {
        $schedule = $this->schedule();

        $next = $schedule->nextOccurrence(['07:00'], false, new DateTimeImmutable('2026-10-09 10:00 Europe/Paris'));

        self::assertSame($this->timestamp('2026-10-10 07:00'), $next);
    }

    public function testSendingTimesAreOrderedWhenFindingTheNextOccurrence(): void
    {
        $next = $this->schedule()->nextOccurrence(
            ['15:00', '09:00'],
            false,
            new DateTimeImmutable('2026-10-09 08:00 Europe/Paris'),
        );

        self::assertSame($this->timestamp('2026-10-09 09:00'), $next);
    }

    public function testWeekendIsSkipped(): void
    {
        $next = $this->schedule()->nextOccurrence(
            ['09:00', '15:00'],
            true,
            new DateTimeImmutable('2026-10-09 16:00 Europe/Paris'),
        );

        self::assertSame($this->timestamp('2026-10-12 09:00'), $next);
    }

    public function testWeekendCatchUpWaitsForTheFirstMondayTime(): void
    {
        $domain = new Domain();
        $domain->setActive(true)->setReportTimes(['09:00', '15:00'])->setReportSkipWeekends(true);
        $user = new User();
        $user->setDomain($domain)->setEmail('user@example.com')->setRoles('["ROLE_USER"]');
        $schedule = $this->schedule();
        $fridaySlot = $this->timestamp('2026-10-09 15:00');

        self::assertSame($this->timestamp('2026-10-12 09:00'), $schedule->deferUntil(
            $user,
            $fridaySlot,
            new DateTimeImmutable('2026-10-12 00:01 Europe/Paris'),
        ));
        self::assertNull($schedule->deferUntil(
            $user,
            $fridaySlot,
            new DateTimeImmutable('2026-10-12 09:03 Europe/Paris'),
        ));
    }

    public function testNonexistentTimeIsSkipped(): void
    {
        $next = $this->schedule()->nextOccurrence(
            ['02:30'],
            false,
            new DateTimeImmutable('2026-03-28 03:00 Europe/Paris'),
        );

        self::assertSame($this->timestamp('2026-03-30 02:30'), $next);
    }

    public function testRepeatedTimeDoesNotAddAnotherOccurrence(): void
    {
        $next = $this->schedule()->nextOccurrence(
            ['02:30'],
            false,
            new DateTimeImmutable('2026-10-25T02:30:00+02:00'),
        );

        self::assertSame($this->timestamp('2026-10-26 02:30'), $next);
    }

    private function schedule(): ReportSchedule
    {
        return new ReportSchedule(new MockClock(), 'Europe/Paris');
    }

    private function timestamp(string $date): int
    {
        return new DateTimeImmutable($date . ' Europe/Paris')->getTimestamp();
    }
}
