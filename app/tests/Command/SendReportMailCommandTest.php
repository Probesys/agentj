<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Util\Url;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendReportMailCommandTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CommandHelper;
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Persistence initializes the next sending time, so freeze the clock
        // before any recipient is created, not just before running the command.
        self::mockTime('2026-10-09 08:00:00 Europe/Paris');
    }

    public function testFirstReportIncludesAllReportableMessages(): void
    {
        self::mockTime('2026-10-09 09:00:00 Europe/Paris');
        $user = $this->createUser();
        $this->createMessage($user, '2025-01-01 10:00:00', 'old-message');
        $this->createMessage($user, '2026-10-09 08:30:00', 'recent-message');

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $user->getEmail());
        self::assertEmailHtmlBodyContains($mail, 'old-message');
        self::assertEmailHtmlBodyContains($mail, 'recent-message');
        $this->refresh($user);
        self::assertSame($this->timestamp('2026-10-09 09:00:00'), $user->getDateLastReport());
    }

    public function testMultipleReportsCoverOnlyNewMessagesAndAreNotSentTwiceInTheSameMinute(): void
    {
        self::mockTime('2026-10-09 09:00:00 Europe/Paris');
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30:00', 'morning-message');
        $this->createMessage($user, '2026-10-09 09:00:10', 'afternoon-message');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailHtmlBodyNotContains(self::getMailerMessage(), 'afternoon-message');

        self::mockTime('2026-10-09 09:00:20 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(1);

        self::mockTime('2026-10-09 15:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(2);
        $mail = self::getMailerMessage(1);
        self::assertEmailHtmlBodyContains($mail, 'afternoon-message');
        self::assertEmailHtmlBodyNotContains($mail, 'morning-message');
    }

    public function testUserScheduleOverridesDomainSchedule(): void
    {
        $user = $this->createUser();
        $user->setReportTimes(['11:00']);
        $this->save($user);
        $this->createMessage($user, '2026-10-09 08:30:00');

        self::mockTime('2026-10-09 09:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(0);

        self::mockTime('2026-10-09 11:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(1);
    }

    public function testSpamReportsRespectTheirPeriodAndThreshold(): void
    {
        self::mockTime('2026-10-09 15:00:00 Europe/Paris');
        $user = $this->createUser();
        $user->getDomain()->setReportSpamLevel(3);
        $user->setDateLastReport($this->timestamp('2026-10-09 09:00:00'));
        $this->save($user);
        $this->createMessage($user, '2026-10-09 08:30:00', 'old-spam', MessageStatus::SPAMMED, 2);
        $this->createMessage($user, '2026-10-09 10:00:00', 'new-spam', MessageStatus::SPAMMED, 2);
        $this->createMessage($user, '2026-10-09 10:30:00', 'high-spam', MessageStatus::SPAMMED, 10);

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailHtmlBodyContains($mail, 'new-spam');
        self::assertEmailHtmlBodyNotContains($mail, 'old-spam');
        self::assertEmailHtmlBodyNotContains($mail, 'high-spam');
    }

    public function testWeekendsDoNotAdvanceTheReportPeriod(): void
    {
        $user = $this->createUser();
        $user->setDateLastReport($this->timestamp('2026-10-09 15:00:00'));
        $user->getDomain()->setReportSkipWeekends(true);
        $this->save($user);

        $this->createMessage($user, '2026-10-10 08:30:00', 'weekend-message');
        self::mockTime('2026-10-10 09:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::mockTime('2026-10-11 15:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(0);
        $this->refresh($user);
        self::assertSame($this->timestamp('2026-10-09 15:00:00'), $user->getDateLastReport());

        self::mockTime('2026-10-12 09:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        self::assertEmailHtmlBodyContains(self::getMailerMessage(), 'weekend-message');
    }

    public function testEmptyReportDoesNotAdvanceTheReportPeriod(): void
    {
        $user = $this->createUser();
        $lastReport = $this->timestamp('2026-10-09 15:00:00');
        $user->setDateLastReport($lastReport);
        $this->save($user);

        self::mockTime('2026-10-12 09:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(0);
        $this->refresh($user);
        self::assertSame($lastReport, $user->getDateLastReport());

        $this->createMessage($user, '2026-10-12 10:00:00', 'new-message');
        self::mockTime('2026-10-12 15:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
        self::assertEmailHtmlBodyContains(self::getMailerMessage(), 'new-message');
    }

    public function testPersonalScheduleCanEnableWeekendReports(): void
    {
        $user = $this->createUser();
        $user->getDomain()->setReportSkipWeekends(true);
        $user->setReportTimes(['11:00']);
        $user->setReportSkipWeekends(false);
        $this->save($user);
        $this->createMessage($user, '2026-10-10 08:30:00');

        self::mockTime('2026-10-10 11:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
    }

    public function testPersonalScheduleCanDisableWeekendReports(): void
    {
        $user = $this->createUser();
        $user->setReportTimes(['11:00']);
        $user->setReportSkipWeekends(true);
        $this->save($user);
        $this->createMessage($user, '2026-10-10 08:30:00');

        self::mockTime('2026-10-10 11:00:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(0);
    }

    public function testSendingTimesUseApplicationTimeZone(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30:00');
        self::mockTime('2026-10-09 07:00:00 UTC');

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
    }

    public function testQueueingDoesNotAdvanceTheReportDate(): void
    {
        self::mockTime('2026-10-09 09:00:00 Europe/Paris');
        $user = $this->createUser();
        $lastReport = $this->timestamp('2026-10-08 15:00:00');
        $user->setDateLastReport($lastReport);
        $this->save($user);
        $this->createMessage($user, '2026-10-09 08:30:00');
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async', $transport);

        $command = self::executeCommand('agentj:send-report-mail');

        $command->assertCommandIsSuccessful();
        self::assertCount(1, $transport->getSent());
        self::assertEmailCount(0);
        $this->refresh($user);
        self::assertSame($lastReport, $user->getDateLastReport());
    }

    public function testManualSendingStillRespectsDisabledReports(): void
    {
        $user = $this->createUser();
        $user->setReport(false);
        $this->save($user);
        $this->createMessage($user, '2026-10-09 08:30:00');
        self::mockTime('2026-10-09 10:00:00 Europe/Paris');

        self::executeCommand('agentj:send-report-mail', ['--force' => true])->assertCommandIsSuccessful();

        self::assertEmailCount(0);
        self::assertNull($user->getDateLastReport());
    }

    public function testManualSendingBypassesSchedule(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30:00');
        self::mockTime('2026-10-09 10:00:00 Europe/Paris');

        self::executeCommand('agentj:send-report-mail', ['--force' => true])->assertCommandIsSuccessful();

        self::assertEmailCount(1);
    }

    public function testMissedSlotIsPickedUpOnTheNextRun(): void
    {
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30:00');
        self::mockTime('2026-10-09 09:03 Europe/Paris');

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(1);
        self::assertSame($this->timestamp('2026-10-09 15:00:00'), $user->getReportNextAt());
    }

    public function testWeekendCatchUpWaitsForMondaySendingTime(): void
    {
        self::mockTime('2026-10-09 08:00 Europe/Paris');
        $user = $this->createUser();
        $user->getDomain()->setReportSkipWeekends(true);
        $this->save($user->getDomain());
        $user->setDateLastReport($this->timestamp('2026-10-08 15:00:00'));
        $this->save($user);
        $this->createMessage($user, '2026-10-10 10:00:00', 'weekend-message');
        self::mockTime('2026-10-12 00:01 Europe/Paris');

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertEmailCount(0);
        self::assertSame($this->timestamp('2026-10-12 09:00:00'), $user->getReportNextAt());
        self::mockTime('2026-10-12 09:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        self::assertEmailHtmlBodyContains(self::getMailerMessage(), 'weekend-message');
    }

    public function testNormalSendingDoesNotQueueAnotherReportWhileOneIsPending(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $this->createUser();
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async', $transport);
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::mockTime('2026-10-09 15:00 Europe/Paris');

        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        self::assertCount(1, $transport->getSent());
        self::assertSame(1, self::getContainer()->get(\App\Repository\ReportDeliveryRepository::class)->count([]));
    }

    public function testManualSendingRefusesAnAlreadyPendingReport(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $this->createUser();
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async', $transport);
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        $command = self::executeCommand('agentj:send-report-mail', ['--force' => true]);

        $command->assertCommandIsSuccessful();
        self::assertCount(1, $transport->getSent());
        self::assertStringContainsString('Report already pending', $command->getDisplay());
        self::assertStringContainsString('ignores schedules and weekends', $command->getDisplay());
    }

    public function testDatabaseRefusesTwoPendingDeliveriesForDifferentSlotsOfTheSameUser(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $user = $this->createUser();
        self::getContainer()->set('messenger.transport.async', new InMemoryTransport());
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

        $this->expectException(UniqueConstraintViolationException::class);
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->insert('report_delivery', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'scheduled_at' => $this->timestamp('2026-10-09 15:00:00'),
            'forced' => 0,
        ]);
    }

    public function testQueueFailureDoesNotConsumeTheDueSlot(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $user = $this->createUser();
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willThrowException(new \RuntimeException('Queue unavailable'));
        self::getContainer()->set('messenger.transport.async', $transport);

        $command = self::executeCommand('agentj:send-report-mail');

        self::assertSame(1, $command->getStatusCode());
        $this->refresh($user);
        self::assertSame($this->timestamp('2026-10-09 09:00:00'), $user->getReportNextAt());
        self::assertNull($user->getDateLastReport());
        self::assertSame(0, self::getContainer()->get(\App\Repository\ReportDeliveryRepository::class)->count([]));
    }

    public function testManualSendingCanBypassWeekendRestrictions(): void
    {
        self::mockTime('2026-10-10 10:00 Europe/Paris');
        $user = $this->createUser();
        $user->getDomain()->setReportSkipWeekends(true);
        $this->save($user->getDomain());
        $this->createMessage($user, '2026-10-10 09:30:00');

        self::executeCommand('agentj:send-report-mail', ['--force' => true])->assertCommandIsSuccessful();

        self::assertEmailCount(1);
        self::assertSame($this->timestamp('2026-10-12 09:00:00'), $user->getReportNextAt());
    }

    private function createUser(): User
    {
        $domain = DomainFactory::createOne([
            'reportTimes' => ['09:00', '15:00'],
            'messageAlert' => '[LIST_MAIL_MSGS]',
        ]);

        return UserFactory::new()->user($domain)->create();
    }

    private function createMessage(
        User $user,
        string $date,
        string $subject = 'report-message',
        int $status = MessageStatus::UNTREATED,
        float $spamLevel = -1.2,
    ): void {
        $sender = UserFactory::new()->user($user->getDomain())->create(['report' => false]);
        $domain = Url::reverseDomainName($user->getDomain()->getDomain());
        $addrS = AddressFactory::findOrCreate([
            'partitionTag' => 0,
            'email' => $sender->getEmail(),
            'domain' => $domain,
        ]);
        $addrR = AddressFactory::findOrCreate(['partitionTag' => 0, 'email' => $user->getEmail(), 'domain' => $domain]);
        $message = $this->setupMail(
            $addrS,
            [$addrR],
            subject: $subject,
            status: $status,
            messageAttributes: ['timeNum' => $this->timestamp($date)],
        );
        $recipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($recipient);
        $recipient->setBspamLevel($spamLevel);
        $this->save($recipient);
    }

    private function timestamp(string $date): int
    {
        return new DateTimeImmutable($date, new \DateTimeZone('Europe/Paris'))->getTimestamp();
    }
}
