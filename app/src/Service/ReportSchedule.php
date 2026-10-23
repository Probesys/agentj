<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ReportSchedule
{
    public function __construct(
        private ClockInterface $clock,
        #[Autowire(param: 'timezone')]
        private string $timezone,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->clock->now()->setTimezone(new DateTimeZone($this->timezone));
    }

    public function isEnabled(User $user): bool
    {
        if (!$user->getReport() || $user->isAdmin() || $user->getOriginalUser() !== null) {
            return false;
        }

        $email = $user->getEmail();
        if ($email === null || str_starts_with($email, '@')) {
            return false;
        }

        return $user->getDomain()?->getActive() === true;
    }

    public function nextAt(User $user, DateTimeImmutable $after, bool $inclusive = false): ?int
    {
        if (!$this->isEnabled($user)) {
            return null;
        }

        return $this->nextOccurrence(
            $user->getEffectiveReportTimes(),
            $user->getEffectiveReportSkipWeekends(),
            $after,
            $inclusive,
        );
    }

    /**
     * @param list<string> $times
     */
    public function nextOccurrence(
        array $times,
        bool $skipWeekends,
        DateTimeImmutable $after,
        bool $inclusive = false,
    ): ?int {
        sort($times);
        $day = $after->setTimezone(new DateTimeZone($this->timezone))->setTime(0, 0);
        for ($offset = 0; $offset < 8; $offset++, $day = $day->modify('+1 day')) {
            if ($skipWeekends && (int) $day->format('N') >= 6) {
                continue;
            }

            foreach ($times as $time) {
                [$hour, $minute] = explode(':', $time);
                $candidate = $day->setTime((int) $hour, (int) $minute);
                if ($candidate->format('H:i') !== $time) {
                    continue;
                }

                if ($candidate > $after || ($inclusive && $candidate == $after)) {
                    return $candidate->getTimestamp();
                }
            }
        }

        return null;
    }

    public function deferUntil(User $user, int $scheduledAt, DateTimeImmutable $now): ?int
    {
        if (!$user->getEffectiveReportSkipWeekends()) {
            return null;
        }

        if ((int) $now->format('N') >= 6) {
            return $this->nextAt($user, $now);
        }

        $today = $now->setTime(0, 0);
        if ($now->format('N') === '1' && $scheduledAt < $today->getTimestamp()) {
            $firstMondayTime = $this->nextAt($user, $today, inclusive: true);
            if ($firstMondayTime !== null && $firstMondayTime > $now->getTimestamp()) {
                return $firstMondayTime;
            }
        }

        return null;
    }
}
