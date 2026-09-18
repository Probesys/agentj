<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Command\AmavisAutoReleaseCommand;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRecipientSearchRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Service\MessageService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AmavisAutoReleaseCommandTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAmavisCheckParentAliasUserToReleaseMail(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        $sender = UserFactory::new()->user($otherDomain)->create();
        $alias = UserFactory::new()->alias($recipient)->create([
            'email' => 'alias@' . $otherDomain,
        ]);
        [$addrS, $addrR] = $this->setupAddresses($sender, $alias);
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testAmavisRestoreMailIfHumanAuthenticationDisabledAndNotDetectedAsSpam(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient->setHumanAuthenticationEnabled(false);
        $sender = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

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
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setBspamLevel(100);
        $this->entityManager->persist($messageRecipient);
        $this->entityManager->flush();

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

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
        $sender = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        // Allow the sender
        $ruleAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        SenderRuleFactory::new()->create([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => 100,
        ]);

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

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
        $mailingListSender = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($mailingListSender, $recipient);
        $fromEmail = 'address@' . $otherDomain->getDomain();
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED, messageAttributes: [
            'fromAddr' => "Alix <$fromEmail>",
        ]);
        // Allow the mailing list address `my-list@mailing.example.org`
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

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

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
        $mailingListEmail = 'my-list@' . $otherDomain->getDomain();
        $mailingListSender = UserFactory::new()->user($otherDomain)->create([
            'email' => $mailingListEmail,
        ]);
        $otherUser = UserFactory::new()->user($otherDomain)->create();
        [$addrS, $addrR] = $this->setupAddresses($mailingListSender, $recipient);
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED, messageAttributes: [
            'fromAddr' => "Alix <address@{$otherDomain->getDomain()}>",
        ]);
        // Allow the other user
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

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testExecuteFailsWhenAutoReleaseLockCannotBeAcquired(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $lock
            ->expects(self::once())
            ->method('acquire')
            ->willReturn(false);

        $lockFactory = $this->createMock(LockFactory::class);
        // Take a lock, to prevent the command to acquire one
        $lockFactory
            ->expects(self::once())
            ->method('createLock')
            ->with('msgs-auto-release', 1800)
            ->willReturn($lock);

        $command = new AmavisAutoReleaseCommand(
            $this->createMock(MessageRecipientRepository::class),
            $this->createMock(MessageRecipientSearchRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(SenderRuleRepository::class),
            $this->createMock(MessageService::class),
            $lockFactory,
        );
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            "Can't acquire the msgs-auto-release lock, the command is probably already running.",
            $commandTester->getDisplay(),
        );
    }
}
