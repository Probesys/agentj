<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
}
