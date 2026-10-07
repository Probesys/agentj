<?php

namespace App\Tests\Command;

use App\Amavis\DeliveryStatus;
use App\Amavis\MessageStatus;
use App\Command\AmavisAutoReleaseCommand;
use App\Message\AmavisAutoRelease;
use App\Message\AmavisRelease;
use App\Repository\MessageRecipientRepository;
use App\Service\MessageService;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AmavisAutoReleaseCommandTest extends KernelTestCase
{
    use CommandHelper;
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testAmavisRestoreMailIfHumanAuthenticationDisabledAndNotDetectedAsSpam(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        $sender = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testAmavisCheckParentAliasUserToReleaseMail(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        $sender = UserFactory::new()->user($otherDomain)->create();
        $alias = UserFactory::new()->alias($recipient)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $alias);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testAmavisPassMailInSpamIfSpamDetected(): void
    {
        $domain = DomainFactory::createOne([
            'level' => 0,
        ]);
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setBspamLevel(100);
        $this->save($messageRecipient);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
    }

    public function testAmavisReleaseMailUsingSenderRuleAddress(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $mailingListEmail = 'my-list@' . $otherDomain->getDomain();
        $mailingListSender = UserFactory::new()->user($otherDomain)->create([
            'email' => $mailingListEmail,
        ]);
        [$addrS, $addrR] = $this->setupAddresses($mailingListSender, $recipient);
        $fromEmail = 'address@' . $otherDomain->getDomain();
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED, messageAttributes: [
            'fromAddr' => "Alix <$fromEmail>",
        ]);
        // Allow the sender `my-list@other.domain.tld`
        $ruleAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => $addrS->getEmail(),
        ]);
        SenderRuleFactory::new()->create([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => 100,
        ]);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
    }

    public function testAmavisReleaseMailUsingFromAddress(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $mailingListEmail = 'my-list@' . $otherDomain->getDomain();
        $mailingListSender = UserFactory::new()->user($otherDomain)->create([
            'email' => $mailingListEmail,
        ]);
        [$addrS, $addrR] = $this->setupAddresses($mailingListSender, $recipient);
        $fromEmail = 'address@' . $otherDomain->getDomain();
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED, messageAttributes: [
            'fromAddr' => "Alix <$fromEmail>",
        ]);
        // Allow the sender `address@other.domain.tld`
        $ruleAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => $fromEmail,
        ]);
        SenderRuleFactory::new()->create([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => 100,
        ]);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
    }

    public function testAmavisDoNotReleaseMailWhenNeitherSenderNorFromAddressMatch(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherUser = UserFactory::new()->user($otherDomain)->create();
        $mailingListEmail = 'my-list@' . $otherDomain->getDomain();
        $mailingListSender = UserFactory::new()->user($otherDomain)->create([
            'email' => $mailingListEmail,
        ]);
        [$addrS, $addrR] = $this->setupAddresses($mailingListSender, $recipient);
        $fromEmail = 'address@' . $otherDomain->getDomain();
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED, messageAttributes: [
            'fromAddr' => "Alix <$fromEmail>",
        ]);
        // Allow an another user `other-user@other.domain.tld`
        $ruleAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => $otherUser->getEmail(),
        ]);
        SenderRuleFactory::new()->create([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => 100,
        ]);

        $applicationTester = self::executeCommand('agentj:auto-release-message');

        $applicationTester->assertCommandIsSuccessful();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testItSkipsOngoingReleasesAndRetriesExpiredOnes(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $ongoing = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        $expired = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        $new = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        self::assertNotFalse($ongoing);
        self::assertNotFalse($expired);
        self::assertNotFalse($new);

        $ongoing->setAmavisReleaseStartedAt(new \DateTimeImmutable('-1 minute'));
        $expired->setAmavisReleaseStartedAt(new \DateTimeImmutable('-11 minutes'));
        $this->save([$ongoing, $expired]);

        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();

        $this->refresh($ongoing);
        $this->refresh($expired);
        $this->refresh($new);
        self::assertSame(MessageStatus::UNRELEASED, $ongoing->getStatus());
        self::assertGreaterThan(new \DateTimeImmutable('-2 minutes'), $expired->getAmavisReleaseStartedAt());
        self::assertNotNull($new->getAmavisReleaseStartedAt());
    }

    public function testItProcessesMixedOutcomesInTheSameBatch(): void
    {
        $domain = DomainFactory::createOne(['level' => 0]);
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $spam = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        $restored = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        self::assertNotFalse($spam);
        self::assertNotFalse($restored);
        $spam->setBspamLevel(100);
        $this->save($spam);

        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();

        $this->refresh($spam);
        $this->refresh($restored);
        self::assertSame(MessageStatus::SPAMMED, $spam->getStatus());
        self::assertNotNull($restored->getAmavisReleaseStartedAt());
    }

    public function testItProcessesEveryBatchWithoutRetryingOngoingOrAlreadyDeliveredMessages(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $mailIds = [];
        for ($i = 0; $i < 5; ++$i) {
            $mailIds[] = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)->getMailId();
        }

        $alreadyDelivered = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        $ongoing = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        self::assertNotFalse($alreadyDelivered);
        self::assertNotFalse($ongoing);
        $alreadyDelivered->setDs(DeliveryStatus::PASS);
        $ongoing->setAmavisReleaseStartedAt(new \DateTimeImmutable());
        $this->save([$alreadyDelivered, $ongoing]);

        // Five eligible messages with a batch size of two require three batches.
        $command = self::getContainer()->get(AmavisAutoReleaseCommand::class);
        (new \ReflectionProperty($command, 'batchSize'))->setValue($command, 2);
        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();

        $repository = self::getContainer()->get(MessageRecipientRepository::class);
        foreach ($mailIds as $mailId) {
            $messageRecipient = $repository->findOneBy(['mailId' => $mailId]);
            self::assertNotNull($messageRecipient);
            self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
        }

        $delivered = $repository->findOneBy(['mailId' => $alreadyDelivered->getMailId()]);
        $inProgress = $repository->findOneBy(['mailId' => $ongoing->getMailId()]);
        self::assertNotNull($delivered);
        self::assertNotNull($inProgress);
        self::assertSame(MessageStatus::UNRELEASED, $delivered->getStatus());
        self::assertSame(MessageStatus::UNRELEASED, $inProgress->getStatus());
    }

    public function testItQueuesTheNextBatchAfterTheReleaseJobs(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $mailIds = [];
        for ($i = 0; $i < 3; ++$i) {
            $mailIds[] = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)->getMailId();
        }

        $bus = new class implements MessageBusInterface {
            /** @var list<object> */
            public array $messages = [];

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->messages[] = $message;
                return Envelope::wrap($message, $stamps);
            }
        };
        $command = self::getContainer()->get(AmavisAutoReleaseCommand::class);
        $messageService = self::getContainer()->get(MessageService::class);
        $commandBus = new \ReflectionProperty($command, 'bus');
        $serviceBus = new \ReflectionProperty($messageService, 'bus');
        $originalCommandBus = $commandBus->getValue($command);
        $originalServiceBus = $serviceBus->getValue($messageService);

        try {
            (new \ReflectionProperty($command, 'batchSize'))->setValue($command, 2);
            $commandBus->setValue($command, $bus);
            $serviceBus->setValue($messageService, $bus);

            self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();
            self::assertCount(3, $bus->messages);
            self::assertInstanceOf(AmavisRelease::class, $bus->messages[0]);
            self::assertInstanceOf(AmavisRelease::class, $bus->messages[1]);
            self::assertInstanceOf(AmavisAutoRelease::class, $bus->messages[2]);

            self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();
            self::assertCount(4, $bus->messages);
            self::assertInstanceOf(AmavisRelease::class, $bus->messages[3]);

            $repository = self::getContainer()->get(MessageRecipientRepository::class);
            foreach ($mailIds as $mailId) {
                $messageRecipient = $repository->findOneBy(['mailId' => $mailId]);
                self::assertNotNull($messageRecipient);
                self::assertNotNull($messageRecipient->getAmavisReleaseStartedAt());
            }
        } finally {
            $commandBus->setValue($command, $originalCommandBus);
            $serviceBus->setValue($messageService, $originalServiceBus);
        }
    }

    public function testRepeatedRecipientsAndSendersDoNotCauseNPlusOneSelects(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $messageRecipients = [];
        for ($i = 0; $i < 25; ++$i) {
            $messageRecipient = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
                ->getMessageRecipients()->first();
            self::assertNotFalse($messageRecipient);
            $messageRecipients[] = $messageRecipient;
        }

        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $questions = function () use ($connection): int {
            $status = $connection->fetchAssociative("SHOW SESSION STATUS LIKE 'Questions'");
            self::assertNotFalse($status);
            return (int) $status['Value'];
        };
        $before = $questions();
        $startedAt = hrtime(true);
        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();
        $queries = $questions() - $before;
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        if (getenv('AUTO_RELEASE_BENCHMARK') === '1') {
            fwrite(STDERR, sprintf("Auto-release: 25 messages, %d SQL queries, %.1f ms\n", $queries, $elapsedMs));
        }

        // One batch SELECT, one user lookup, one sender rule lookup, then 25 updates.
        // Per-message lookups or flushes would break this bound.
        self::assertLessThan(40, $queries);
        foreach ($messageRecipients as $messageRecipient) {
            $this->refresh($messageRecipient);
            self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
        }
    }
}
