<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendReportMailCommandTest extends KernelTestCase
{
    use CommandHelper;
    use FactoryHelper;
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testReportMailIsSentToUserThatActivatedReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);

        $applicationTester = self::executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrR->getEmail());
    }

    public function testReportMailIsNotSentToUsersThatDidNotActivateReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create([
            'report' => false,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);

        $applicationTester = $this->executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testReportMailIsNotSentToAliasesOfUserThatActivatedReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->alias($recipient1)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, [$addrR2], status: MessageStatus::UNTREATED);

        $applicationTester = $this->executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        // Only 1 mail sent to recipient1.
        // Recipient2 is an alias, so report is not sent to him.
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrR->getEmail());
    }

    public function testReportMailIsNotSentToUserThatDoNotHaveUntreatedMails(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);

        $applicationTester = $this->executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testReportMailContainsSpamMessagesUnderDomainReportSpamLevel(): void
    {
        $domain = DomainFactory::createOne([
            'reportSpamLevel' => 2,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setBspamLevel(1);
        $this->save($messageRecipient);

        $applicationTester = $this->executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        // Email sent to recipient1, including spam (because under domain spam threshold).
        self::assertEmailCount(1);
    }

    public function testReportMailDoesNotContainSpamMessagesAboveDomainReportSpamLevel(): void
    {
        $domain = DomainFactory::createOne([
            'reportSpamLevel' => 2,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setBspamLevel(5);
        $this->save($messageRecipient);

        $applicationTester = $this->executeCommand('agentj:send-report-mail');

        $applicationTester->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }
}
