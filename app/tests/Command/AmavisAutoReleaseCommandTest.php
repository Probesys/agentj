<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
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
}
