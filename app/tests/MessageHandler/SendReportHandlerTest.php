<?php

namespace App\Tests\MessageHandler;

use App\Amavis\MessageStatus;
use App\Entity\User;
use App\EventSubscriber\ReportFailureSubscriber;
use App\Message\SendReport;
use App\MessageHandler\SendReportHandler;
use App\Repository\ReportDeliveryRepository;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Mailer\Envelope as MailEnvelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendReportHandlerTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CommandHelper;
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    private ?InMemoryTransport $outbox = null;

    public function testContentIsPreparedWhenTheWorkerRuns(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30', 'before-slot');
        $message = $this->queueReport();
        $this->createMessage($user, '2026-10-09 09:02', 'while-queued');
        self::mockTime('2026-10-09 09:05 Europe/Paris');

        self::getContainer()->get(SendReportHandler::class)($message);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailHtmlBodyContains($email, 'before-slot');
        self::assertEmailHtmlBodyContains($email, 'while-queued');
        $this->refresh($user);
        self::assertSame($this->timestamp('2026-10-09 09:05'), $user->getDateLastReport());
        self::assertSame(0, $this->deliveries()->count([]));
    }

    #[DataProvider('arrivalsDuringSending')]
    public function testMessagesArrivingDuringSendingRemainForTheNextReport(string $at): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30', 'initial-message');
        $this->createMessage($user, '2026-10-09 09:05:00', 'at-cutoff-before-preparation');
        $message = $this->queueReport();
        $captured = [];
        $smtp = $this->createMock(TransportInterface::class);
        $send = function (RawMessage $email) use ($user, $at, &$captured): SentMessage {
            self::assertInstanceOf(Email::class, $email);
            $captured[] = $email;
            if (count($captured) === 1) {
                $this->createMessage($user, $at, 'during-send');
                self::mockTime('2026-10-09 09:05:30 Europe/Paris');
            }

            return new SentMessage($email, MailEnvelope::create($email));
        };
        $smtp->method('send')->willReturnCallback($send);
        self::getContainer()->set('mailer.transports', $smtp);
        self::mockTime('2026-10-09 09:05:00 Europe/Paris');
        $handler = self::getContainer()->get(SendReportHandler::class);

        $handler($message);

        self::assertSame($this->timestamp('2026-10-09 09:05:00'), $user->getReportPeriodEnd());
        self::assertSame($this->timestamp('2026-10-09 09:05:30'), $user->getDateLastReport());
        self::assertEmailHtmlBodyNotContains($captured[0], 'during-send');
        self::assertEmailHtmlBodyNotContains($captured[0], 'at-cutoff-before-preparation');
        self::mockTime('2026-10-09 15:00 Europe/Paris');
        $handler($this->queueReport());
        self::assertEmailHtmlBodyContains($captured[1], 'during-send');
        self::assertEmailHtmlBodyContains($captured[1], 'at-cutoff-before-preparation');
        self::assertEmailHtmlBodyNotContains($captured[1], 'initial-message');
    }

    /** @return iterable<string, array{string}> */
    public static function arrivalsDuringSending(): iterable
    {
        yield 'same second as cutoff' => ['2026-10-09 09:05:00'];
        yield 'after cutoff' => ['2026-10-09 09:05:10'];
    }

    public function testRetryRetainsThePreparedContentAndMessageId(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30', 'initial-message');
        $message = $this->queueReport();
        $captured = [];
        $smtp = $this->createMock(TransportInterface::class);
        $smtp->method('send')->willReturnCallback(function (RawMessage $email) use (&$captured): SentMessage {
            self::assertInstanceOf(Email::class, $email);
            $captured[] = $email;
            if (count($captured) === 1) {
                throw new TransportException('SMTP unavailable');
            }

            return new SentMessage($email, MailEnvelope::create($email));
        });
        self::getContainer()->set('mailer.transports', $smtp);
        $handler = self::getContainer()->get(SendReportHandler::class);
        try {
            $handler($message);
            self::fail('The SMTP failure should be propagated for Messenger retries.');
        } catch (TransportException $exception) {
            self::assertSame('SMTP unavailable', $exception->getMessage());
        }

        self::assertNull($user->getDateLastReport());
        self::assertNull($user->getReportPeriodEnd());
        self::assertNotNull($this->deliveries()->find($message->reportId)->getContent());
        $user->getDomain()->setMessageAlert('changed-template');
        $this->save($user->getDomain());
        $this->createMessage($user, '2026-10-09 09:05', 'later-message');
        self::mockTime('2026-10-09 09:10 Europe/Paris');

        $handler($message);

        self::assertSame($captured[0]->getHtmlBody(), $captured[1]->getHtmlBody());
        self::assertSame($captured[0]->getTextBody(), $captured[1]->getTextBody());
        self::assertNotNull($captured[0]->getHeaders()->get('Message-ID'));
        self::assertSame(
            $captured[0]->getHeaders()->get('Message-ID')->getBodyAsString(),
            $captured[1]->getHeaders()->get('Message-ID')?->getBodyAsString(),
        );
        self::assertEmailHtmlBodyNotContains($captured[1], 'later-message');
        self::assertSame($this->timestamp('2026-10-09 09:00'), $user->getReportPeriodEnd());
        self::assertSame($this->timestamp('2026-10-09 09:10'), $user->getDateLastReport());
    }

    public function testPendingRetryCannotBeOvertakenByANewerReport(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30', 'first-period');
        $message = $this->queueReport();
        $captured = [];
        $smtp = $this->createMock(TransportInterface::class);
        $smtp->method('send')->willReturnCallback(function (RawMessage $email) use (&$captured): SentMessage {
            self::assertInstanceOf(Email::class, $email);
            $captured[] = $email;
            if (count($captured) === 1) {
                throw new TransportException('SMTP unavailable');
            }
            return new SentMessage($email, MailEnvelope::create($email));
        });
        self::getContainer()->set('mailer.transports', $smtp);
        $handler = self::getContainer()->get(SendReportHandler::class);
        try {
            $handler($message);
            self::fail('The first attempt must fail.');
        } catch (TransportException) {
        }

        $this->createMessage($user, '2026-10-09 12:00', 'second-period');
        self::mockTime('2026-10-09 15:00 Europe/Paris');
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertNotNull($this->outbox);
        self::assertCount(1, $this->outbox->getSent());
        self::assertSame(1, $this->deliveries()->count([]));
        self::assertNull($user->getReportPeriodEnd());

        self::mockTime('2026-10-09 15:05 Europe/Paris');
        $handler($message);
        self::assertSame($captured[0]->getHtmlBody(), $captured[1]->getHtmlBody());
        self::assertEmailHtmlBodyContains($captured[1], 'first-period');
        self::assertEmailHtmlBodyNotContains($captured[1], 'second-period');
        self::assertSame(0, $this->deliveries()->count([]));

        self::mockTime('2026-10-09 15:06 Europe/Paris');
        $handler($this->queueReport());
        self::assertCount(3, $captured);
        self::assertEmailHtmlBodyContains($captured[2], 'second-period');
        self::assertEmailHtmlBodyNotContains($captured[2], 'first-period');
        self::assertSame($this->timestamp('2026-10-09 15:06'), $user->getReportPeriodEnd());
    }

    #[DataProvider('cancellations')]
    public function testIneligibleRecipientsAreCancelled(string $change): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30');
        $message = $this->queueReport();
        match ($change) {
            'report' => $user->setReport(false),
            'domain' => $user->getDomain()->setActive(false),
            'email' => $user->setEmail('changed@example.com'),
            default => throw new \LogicException('Unknown cancellation scenario.'),
        };
        $this->save($user);

        self::getContainer()->get(SendReportHandler::class)($message);

        self::assertEmailCount(0);
        self::assertNull($user->getDateLastReport());
        self::assertSame(0, $this->deliveries()->count([]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cancellations(): iterable
    {
        yield 'reports disabled' => ['report'];
        yield 'domain disabled' => ['domain'];
        yield 'email changed' => ['email'];
    }

    public function testDeletedRecipientDoesNotReceiveTheQueuedReport(): void
    {
        $user = $this->createUser();
        $message = $this->queueReport();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->remove($user);
        $em->flush();

        self::getContainer()->get(SendReportHandler::class)($message);

        self::assertEmailCount(0);
    }

    public function testWorkerReloadsEligibilityFromTheDatabase(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30');
        $message = $this->queueReport();
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->update(
            'users',
            ['report' => 0],
            ['id' => $user->getId()],
        );

        self::getContainer()->get(SendReportHandler::class)($message);

        self::assertEmailCount(0);
        self::assertSame(0, $this->deliveries()->count([]));
    }

    public function testChangingTheScheduleKeepsTheAlreadyQueuedReport(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30');
        $message = $this->queueReport();
        self::mockTime('2026-10-09 09:02 Europe/Paris');
        $user->setReportTimes(['11:00']);
        $this->save($user);

        self::getContainer()->get(SendReportHandler::class)($message);

        self::assertEmailCount(1);
        self::assertSame($this->timestamp('2026-10-09 11:00'), $user->getReportNextAt());
    }

    public function testWeekendDeliveryWaitsForTheFirstMondayTime(): void
    {
        $user = $this->createUser();
        $user->getDomain()->setReportSkipWeekends(true);
        $this->save($user->getDomain());
        $this->createMessage($user, '2026-10-09 08:30');
        $message = $this->queueReport();
        $handler = self::getContainer()->get(SendReportHandler::class);
        self::mockTime('2026-10-10 10:00 Europe/Paris');
        $handler($message);
        self::assertNotNull($this->outbox);
        $deferred = $this->outbox->getSent()[1]->last(DelayStamp::class);
        self::assertNotNull($deferred);
        self::assertSame(
            ($this->timestamp('2026-10-12 09:00') - $this->timestamp('2026-10-10 10:00')) * 1000,
            $deferred->getDelay(),
        );

        self::assertEmailCount(0);
        self::assertNull($user->getDateLastReport());
        self::mockTime('2026-10-12 09:00 Europe/Paris');
        $handler($message);
        self::assertEmailCount(1);
    }

    public function testFinalFailureLetsTheNextSlotCoverTheUnsentPeriod(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30', 'unsent-message');
        $message = $this->queueReport();
        $subscriber = self::getContainer()->get(ReportFailureSubscriber::class);
        $retrying = new WorkerMessageFailedEvent(new Envelope($message), 'async', new TransportException('failure'));
        $retrying->setForRetry();
        $subscriber->onFailure($retrying);
        self::assertSame(1, $this->deliveries()->count([]));

        $subscriber->onFailure(new WorkerMessageFailedEvent(
            new Envelope($message),
            'async',
            new TransportException('retries exhausted'),
        ));
        self::assertSame(0, $this->deliveries()->count([]));
        self::assertNull($user->getDateLastReport());
        self::mockTime('2026-10-09 15:00 Europe/Paris');

        self::getContainer()->get(SendReportHandler::class)($this->queueReport());

        self::assertEmailCount(1);
        self::assertEmailHtmlBodyContains(self::getMailerMessage(), 'unsent-message');
    }

    public function testCompletedDeliveryIsNotSentAgain(): void
    {
        $user = $this->createUser();
        $this->createMessage($user, '2026-10-09 08:30');
        $message = $this->queueReport();
        $handler = self::getContainer()->get(SendReportHandler::class);

        $handler($message);
        $handler($message);

        self::assertEmailCount(1);
    }

    private function createUser(): User
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $domain = DomainFactory::createOne([
            'reportTimes' => ['09:00', '15:00'],
            'messageAlert' => '[LIST_MAIL_MSGS]',
        ]);

        return UserFactory::new()->user($domain)->create();
    }

    private function queueReport(): SendReport
    {
        if ($this->outbox === null) {
            $this->outbox = new InMemoryTransport();
            self::getContainer()->set('messenger.transport.async', $this->outbox);
        } else {
            $this->outbox->reset();
        }
        self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
        self::assertCount(1, $this->outbox->getSent());
        $message = $this->outbox->getSent()[0]->getMessage();
        self::assertInstanceOf(SendReport::class, $message);

        return $message;
    }

    private function deliveries(): ReportDeliveryRepository
    {
        return self::getContainer()->get(ReportDeliveryRepository::class);
    }

    private function createMessage(User $user, string $date, string $subject = 'report-message'): void
    {
        $sender = AddressFactory::findOrCreate(['partitionTag' => 0, 'email' => 'sender@example.net']);
        $recipient = AddressFactory::findOrCreate(['partitionTag' => 0, 'email' => $user->getEmail()]);
        $this->setupMail(
            $sender,
            [$recipient],
            subject: $subject,
            status: MessageStatus::UNTREATED,
            messageAttributes: ['timeNum' => $this->timestamp($date)],
        );
    }

    private function timestamp(string $date): int
    {
        return new DateTimeImmutable($date . ' Europe/Paris')->getTimestamp();
    }
}
