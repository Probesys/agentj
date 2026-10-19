<?php

namespace App\Tests\MessageHandler;

use App\Amavis\MessageStatus;
use App\Repository\ReportDeliveryRepository;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransportFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ReportDoctrineTransportTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use CommandHelper;
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    public function testDoctrineQueueDispatchRecordsSuccessfulSending(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00'], 'messageAlert' => '[LIST_MAIL_MSGS]']);
        $user = UserFactory::new()->user($domain)->create();
        $sender = AddressFactory::createOne(['email' => 'sender@example.net', 'partitionTag' => 0]);
        $recipient = AddressFactory::createOne(['email' => $user->getEmail(), 'partitionTag' => 0]);
        $this->setupMail($sender, [$recipient], status: MessageStatus::UNTREATED, messageAttributes: [
            'timeNum' => new DateTimeImmutable('2026-10-09 08:30 Europe/Paris')->getTimestamp(),
        ]);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->executeStatement(<<<SQL
            CREATE TEMPORARY TABLE report_test_queue (
                id BIGINT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB
        SQL);

        try {
            $factory = new DoctrineTransportFactory(self::getContainer()->get(ManagerRegistry::class));
            $transport = $factory->createTransport('doctrine://default', [
                'table_name' => 'report_test_queue',
                'queue_name' => 'reports',
                'auto_setup' => false,
            ], new PhpSerializer());
            self::assertInstanceOf(DoctrineTransport::class, $transport);
            self::getContainer()->set('messenger.transport.async', $transport);

            self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();

            self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM report_test_queue'));
            self::assertSame(1, self::getContainer()->get(ReportDeliveryRepository::class)->count([]));
            self::assertNull($user->getDateLastReport());
            self::assertEmailCount(0);
            $envelopes = iterator_to_array($transport->get());
            self::assertCount(1, $envelopes);
            self::mockTime('2026-10-09 09:05 Europe/Paris');

            self::getContainer()->get(MessageBusInterface::class)->dispatch(
                $envelopes[0]->with(new ReceivedStamp('async')),
            );
            $transport->ack($envelopes[0]);

            self::assertEmailCount(1);
            self::assertSame(
                new DateTimeImmutable('2026-10-09 09:05 Europe/Paris')->getTimestamp(),
                $user->getDateLastReport(),
            );
            self::assertSame(0, self::getContainer()->get(ReportDeliveryRepository::class)->count([]));
            self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM report_test_queue'));
        } finally {
            $connection->executeStatement('DROP TEMPORARY TABLE report_test_queue');
        }
    }

    public function testWorkerRetriesPreserveContentAndTerminalFailureReleasesTheRecipient(): void
    {
        self::mockTime('2026-10-09 09:00 Europe/Paris');
        $domain = DomainFactory::createOne(['reportTimes' => ['09:00'], 'messageAlert' => '[LIST_MAIL_MSGS]']);
        $user = UserFactory::new()->user($domain)->create();
        $sender = AddressFactory::createOne(['email' => 'sender@example.net', 'partitionTag' => 0]);
        $recipient = AddressFactory::createOne(['email' => $user->getEmail(), 'partitionTag' => 0]);
        $this->setupMail(
            $sender,
            [$recipient],
            subject: 'retry-content',
            status: MessageStatus::UNTREATED,
            messageAttributes: ['timeNum' => new DateTimeImmutable('2026-10-09 08:30 Europe/Paris')->getTimestamp()],
        );
        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement(<<<SQL
            CREATE TEMPORARY TABLE report_retry_queue (
                id BIGINT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB
        SQL);
        try {
            $factory = new DoctrineTransportFactory(self::getContainer()->get(ManagerRegistry::class));
            $transport = $factory->createTransport('doctrine://default', [
                'table_name' => 'report_retry_queue',
                'queue_name' => 'reports',
                'auto_setup' => false,
            ], new PhpSerializer());
            self::getContainer()->set('messenger.transport.async', $transport);
            $captured = [];
            $smtp = $this->createMock(TransportInterface::class);
            $smtp->method('send')->willReturnCallback(function (RawMessage $email) use (&$captured): never {
                self::assertInstanceOf(Email::class, $email);
                $captured[] = $email;
                throw new TransportException('SMTP unavailable');
            });
            self::getContainer()->set('mailer.transports', $smtp);
            self::executeCommand('agentj:send-report-mail')->assertCommandIsSuccessful();
            $dispatcher = self::getContainer()->get('event_dispatcher');
            self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
            $decisions = [];
            $bus = self::getContainer()->get(MessageBusInterface::class);
            $worker = new Worker(['async' => $transport], $bus, $dispatcher);
            $listener = function (WorkerMessageFailedEvent $event) use (&$decisions, &$worker): void {
                $decisions[] = $event->willRetry();
                $worker->stop();
            };
            // Observe the result after Messenger's retry listener and our cleanup subscriber.
            $dispatcher->addListener(WorkerMessageFailedEvent::class, $listener, -1024);
            try {
                for ($attempt = 0; $attempt < 4; $attempt++) {
                    // Make the real delayed retry available without sleeping in the test.
                    $connection->executeStatement('UPDATE report_retry_queue SET available_at = created_at');
                    $worker = new Worker(['async' => $transport], $bus, $dispatcher);
                    $worker->run(['sleep' => 0, 'time_limit' => 5]);
                    self::assertCount($attempt + 1, $captured);
                    self::assertNull($user->getDateLastReport());
                    self::assertNull($user->getReportPeriodEnd());
                    self::assertSame(
                        $attempt < 3 ? 1 : 0,
                        self::getContainer()->get(ReportDeliveryRepository::class)->count([]),
                    );
                    if ($attempt === 0) {
                        $this->setupMail(
                            $sender,
                            [$recipient],
                            subject: 'during-retry',
                            status: MessageStatus::UNTREATED,
                            messageAttributes: [
                                'timeNum' => new DateTimeImmutable('2026-10-09 09:01 Europe/Paris')->getTimestamp(),
                            ],
                        );
                        self::mockTime('2026-10-09 09:05 Europe/Paris');
                    }
                }
            } finally {
                $dispatcher->removeListener(WorkerMessageFailedEvent::class, $listener);
            }
            self::assertSame([true, true, true, false], $decisions);
            self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM report_retry_queue'));
            self::assertNotNull($captured[0]->getHeaders()->get('Message-ID'));
            foreach ($captured as $email) {
                self::assertEmailHtmlBodyContains($email, 'retry-content');
                self::assertEmailHtmlBodyNotContains($email, 'during-retry');
                self::assertSame($captured[0]->getHtmlBody(), $email->getHtmlBody());
                self::assertSame(
                    $captured[0]->getHeaders()->get('Message-ID')->getBodyAsString(),
                    $email->getHeaders()->get('Message-ID')?->getBodyAsString(),
                );
            }
        } finally {
            $connection->executeStatement('DROP TEMPORARY TABLE report_retry_queue');
        }
    }
}
