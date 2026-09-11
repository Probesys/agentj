<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\SenderRule;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RecoverAuthorizedSpamCommandTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testItReportsThenReleasesFalsePositiveSpam(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($senderAddress, $recipientAddress, status: MessageStatus::SPAMMED);
        $ruleAddress = RuleAddressFactory::createOne(['email' => $sender->getEmail()]);
        SenderRuleFactory::createOne([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => ' ',
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $manuallyMarkedMessage = $this->setupMail(
            $senderAddress,
            $recipientAddress,
            status: MessageStatus::SPAMMED,
        );
        $manuallyMarkedRecipient = $manuallyMarkedMessage->getMessageRecipients()->first();
        self::assertNotFalse($manuallyMarkedRecipient);
        static::getContainer()->get('doctrine.dbal.default_connection')->executeStatement(<<<'SQL'
            INSERT INTO log (action, mailId, details, created, updated)
            VALUES ('marked as spam', :mailId, '', NOW(), NOW())
        SQL, ['mailId' => $manuallyMarkedMessage->getMailId()]);

        $application = new Application(static::createKernel());
        $command = $application->find('agentj:recover-authorized-spam');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            '--since' => '1970-01-01T00:00:00+00:00',
            '--batch-size' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('1 would be released', $commandTester->getDisplay());
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());

        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            '--since' => '1970-01-01T00:00:00+00:00',
            '--release' => true,
            '--batch-size' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('1 were queued for release', $commandTester->getDisplay());
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        $this->refresh($manuallyMarkedRecipient);
        self::assertSame(MessageStatus::SPAMMED, $manuallyMarkedRecipient->getStatus());
    }
}
