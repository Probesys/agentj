<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Message\AmavisAutoRelease;
use App\Message\AmavisRelease;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
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

    public function testExecuteSkipsOngoingReleasesAndRetriesExpiredOnes(): void
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

        $ongoingStartedAt = new \DateTimeImmutable('-1 minute');
        $ongoing->setAmavisReleaseStartedAt($ongoingStartedAt);
        $expired->setAmavisReleaseStartedAt(new \DateTimeImmutable('-11 minutes'));
        $this->save([$ongoing, $expired]);

        // Database timestamps have second precision: compare against the start of the command.
        $startedAt = new \DateTimeImmutable(date('Y-m-d H:i:s'));
        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();

        $this->refresh($ongoing);
        $this->refresh($expired);
        $this->refresh($new);
        self::assertSame(MessageStatus::UNRELEASED, $ongoing->getStatus());
        self::assertSame($ongoingStartedAt->getTimestamp(), $ongoing->getAmavisReleaseStartedAt()?->getTimestamp());
        self::assertSame(MessageStatus::RESTORED, $expired->getStatus());
        self::assertSame(MessageStatus::RESTORED, $new->getStatus());
        self::assertGreaterThanOrEqual($startedAt, $expired->getAmavisReleaseStartedAt());
        self::assertGreaterThanOrEqual($startedAt, $new->getAmavisReleaseStartedAt());
    }

    public function testExecuteProcessesMixedOutcomesInTheSameBatch(): void
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
        self::assertSame(MessageStatus::RESTORED, $restored->getStatus());
        self::assertNotNull($restored->getAmavisReleaseStartedAt());
    }

    public function testExecuteProcessesEveryBatchWithoutRetryingOngoingReleases(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $messageRecipients = [];
        for ($i = 0; $i < 5; ++$i) {
            $messageRecipient = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
                ->getMessageRecipients()->first();
            self::assertNotFalse($messageRecipient);
            $messageRecipients[] = $messageRecipient;
        }

        $ongoing = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
            ->getMessageRecipients()->first();
        self::assertNotFalse($ongoing);
        $ongoing->setAmavisReleaseStartedAt(new \DateTimeImmutable());
        $this->save($ongoing);

        // Five eligible messages with a batch size of two require three batches.
        self::executeCommand('agentj:auto-release-message', ['--batch-size' => 2])->assertCommandIsSuccessful();

        foreach ($messageRecipients as $messageRecipient) {
            $messageRecipient = $this->reloadMessageRecipient($messageRecipient);
            self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
        }

        $ongoing = $this->reloadMessageRecipient($ongoing);
        self::assertSame(MessageStatus::UNRELEASED, $ongoing->getStatus());
    }

    public function testExecuteQueuesTheNextBatchAfterTheReleaseJobs(): void
    {
        $bus = new class implements MessageBusInterface {
            /** @var list<object> */
            public array $messages = [];

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->messages[] = $message;
                return Envelope::wrap($message, $stamps);
            }
        };
        // Replace the bus before fixtures initialize Doctrine listeners and their services.
        self::getContainer()->set('messenger.bus.default', $bus);

        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);

        $messageRecipients = [];
        for ($i = 0; $i < 3; ++$i) {
            $messageRecipient = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED)
                ->getMessageRecipients()->first();
            self::assertNotFalse($messageRecipient);
            $messageRecipients[] = $messageRecipient;
        }

        self::executeCommand('agentj:auto-release-message', ['--batch-size' => 2])->assertCommandIsSuccessful();
        self::assertCount(3, $bus->messages);
        self::assertInstanceOf(AmavisRelease::class, $bus->messages[0]);
        self::assertInstanceOf(AmavisRelease::class, $bus->messages[1]);
        self::assertInstanceOf(AmavisAutoRelease::class, $bus->messages[2]);
        self::assertSame(2, $bus->messages[2]->batchSize);

        self::executeCommand('agentj:auto-release-message', ['--batch-size' => 2])->assertCommandIsSuccessful();
        self::assertCount(4, $bus->messages);
        self::assertInstanceOf(AmavisRelease::class, $bus->messages[3]);

        foreach ($messageRecipients as $messageRecipient) {
            $messageRecipient = $this->reloadMessageRecipient($messageRecipient);
            self::assertNotNull($messageRecipient->getAmavisReleaseStartedAt());
        }
    }

    public function testExecuteRejectsInvalidBatchSizes(): void
    {
        foreach (['0', '-1', '1.5', 'invalid'] as $batchSize) {
            $tester = self::executeCommand('agentj:auto-release-message', ['--batch-size' => $batchSize]);
            self::assertSame(Command::INVALID, $tester->getStatusCode());
            self::assertStringContainsString('must be a positive integer', $tester->getDisplay());
        }
    }

    public function testExecuteAvoidsNPlusOneSelectsForRepeatedRecipientsAndSenders(): void
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
        // MySQL's session counter measures SQL statements on this connection,
        // including the SHOW query itself; its delta detects per-message lookups.
        $questions = function () use ($connection): int {
            $status = $connection->fetchAssociative("SHOW SESSION STATUS LIKE 'Questions'");
            self::assertNotFalse($status);
            return (int) $status['Value'];
        };
        $before = $questions();
        self::executeCommand('agentj:auto-release-message')->assertCommandIsSuccessful();
        $queries = $questions() - $before;

        // One batch SELECT, one user lookup, one sender rule lookup, then 25 updates.
        // Per-message lookups or flushes would break this bound.
        self::assertLessThan(40, $queries);
        foreach ($messageRecipients as $messageRecipient) {
            $this->refresh($messageRecipient);
            self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
        }
    }

    private function reloadMessageRecipient(MessageRecipient $messageRecipient): MessageRecipient
    {
        // Full batches clear the entity manager, so the stored entities are detached
        // and cannot be refreshed directly.
        return MessageRecipientFactory::find([
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
            'rseqnum' => $messageRecipient->getRseqnum(),
        ]);
    }
}
