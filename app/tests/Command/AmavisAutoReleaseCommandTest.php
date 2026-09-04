<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
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
    use SessionHelper;

    public function testAmavisReleaseMailFromDomain(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
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
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
    }

    public function testAmavisReleaseMailFromMailingList(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($otherDomain)->create([
            'email' => 'tcs-forum-owner@' . $otherDomain->getDomain(),
        ]);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $message->setFromAddr('"Jane Doe" (via tcs-forum Mailing List) <tcs-forum@listes.renater.fr>');

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:auto-release-message');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $messageRecipient = $message->getMessageRecipients()->first();
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
    }
}
