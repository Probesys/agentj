<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageMarkAsCommandTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testMarkAsSpamAffectsRecipientsOfAllDomains(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $application = new Application(static::createKernel());
        $command = $application->find('agentj:messages:mark-as');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'status' => 'spam',
            'mail-id' => $message->getMailId(),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $this->refresh($messageRecipient);
        $this->refresh($otherMessageRecipient);
        // The command is not scoped to any domain: every recipient is affected.
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $otherMessageRecipient->getStatus());
    }

    public function testMarkAsHamAffectsRecipientsOfAllDomains(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::SPAMMED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $application = new Application(static::createKernel());
        $command = $application->find('agentj:messages:mark-as');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            'status' => 'ham',
            'mail-id' => $message->getMailId(),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $this->refresh($messageRecipient);
        $this->refresh($otherMessageRecipient);
        // The command is not scoped to any domain: every recipient is affected.
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::RESTORED, $otherMessageRecipient->getStatus());
    }
}
