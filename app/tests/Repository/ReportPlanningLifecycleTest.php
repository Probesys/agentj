<?php

namespace App\Tests\Repository;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReportPlanningLifecycleTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use Factories;
    use FactoryHelper;
    use ResetDatabase;

    public function testNewUserAfterTheLastSlotWaitsUntilTheNextDay(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 10:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['07:00']]);

        $user = UserFactory::new()->user($domain)->create();

        self::assertSame($this->timestamp('2026-10-10 07:00'), $user->getReportNextAt());
    }

    public function testAddingAnEarlierTimeUsesItsNextFutureOccurrence(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00']]);
        $user = UserFactory::new()->user($domain)->create();
        self::mockTime('2026-10-09 10:00 Europe/Paris');

        $user->setReportTimes(['08:00']);
        $this->save($user);

        self::assertSame($this->timestamp('2026-10-10 08:00'), $user->getReportNextAt());
    }

    public function testReenablingReportsUsesTheNextFutureSlot(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00', '15:00']]);
        $user = UserFactory::new()->user($domain)->create(['report' => false]);
        self::assertNull($user->getReportNextAt());
        self::mockTime('2026-10-09 10:00 Europe/Paris');

        $user->setReport(true);
        $this->save($user);

        self::assertSame($this->timestamp('2026-10-09 15:00'), $user->getReportNextAt());
    }

    public function testDomainScheduleUpdatesOnlyInheritedSchedules(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00', '15:00']]);
        $inherited = UserFactory::new()->user($domain)->create();
        $custom = UserFactory::new()->user($domain)->create(['reportTimes' => ['11:00']]);
        self::mockTime('2026-10-09 10:00 Europe/Paris');

        $domain->setReportTimes(['07:00', '17:00']);
        $this->save($domain);

        $this->refresh($inherited);
        $this->refresh($custom);
        self::assertSame($this->timestamp('2026-10-09 17:00'), $inherited->getReportNextAt());
        self::assertSame($this->timestamp('2026-10-09 11:00'), $custom->getReportNextAt());
    }

    public function testDomainReactivationRestoresInheritedAndCustomSchedules(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['active' => false, 'reportTimes' => ['09:00', '15:00']]);
        $inherited = UserFactory::new()->user($domain)->create();
        $custom = UserFactory::new()->user($domain)->create(['reportTimes' => ['11:00']]);
        self::assertNull($inherited->getReportNextAt());
        self::assertNull($custom->getReportNextAt());
        self::mockTime('2026-10-09 10:00 Europe/Paris');

        $domain->setActive(true);
        $this->save($domain);

        $this->refresh($inherited);
        $this->refresh($custom);
        self::assertSame($this->timestamp('2026-10-09 15:00'), $inherited->getReportNextAt());
        self::assertSame($this->timestamp('2026-10-09 11:00'), $custom->getReportNextAt());
    }

    public function testPersonalTimesStillInheritTheDomainWeekendSetting(): void
    {
        self::bootKernel();
        self::mockTime('2026-10-10 08:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00']]);
        $inheritedWeekend = UserFactory::new()->user($domain)->create(['reportTimes' => ['11:00']]);
        $personalWeekend = UserFactory::new()->user($domain)->create([
            'reportTimes' => ['11:00'],
            'reportSkipWeekends' => false,
        ]);

        $domain->setReportSkipWeekends(true);
        $this->save($domain);

        $this->refresh($inheritedWeekend);
        $this->refresh($personalWeekend);
        self::assertSame($this->timestamp('2026-10-12 11:00'), $inheritedWeekend->getReportNextAt());
        self::assertSame($this->timestamp('2026-10-10 11:00'), $personalWeekend->getReportNextAt());
    }

    private function timestamp(string $date): int
    {
        return new DateTimeImmutable($date . ' Europe/Paris')->getTimestamp();
    }
}
