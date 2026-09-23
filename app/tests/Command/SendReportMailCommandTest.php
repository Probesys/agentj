<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendReportMailCommandTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testReportMailIsSentToUserThatActivatedReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create([
            'report' => false,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, $addrR2, status: MessageStatus::UNTREATED);
        self::bootKernel();
        // Create a mock to intercept mails and assert only 1 mail sent to recipient1
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($recipient): bool {
                    $address = $email->getTo()[0]->getAddress();
                    return $address === $recipient->getEmail();
            }));
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-report-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testReportMailIsNotSentToAliasesOfUserThatActivatedReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->alias($recipient)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, $addrR2, status: MessageStatus::UNTREATED);
        self::bootKernel();
        // Create a mock to intercept mails and assert only 1 mail sent to recipient1.
        // Recipient2 is an alias, so report is not sent to him.
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($recipient): bool {
                    $address = $email->getTo()[0]->getAddress();
                    return $address === $recipient->getEmail();
            }));
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-report-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testReportMailIsNotSentToUserThatDoNotHaveUntreatedMails(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        self::bootKernel();
        // Create a mock to intercept mails and assert 1 is sent to the user
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-report-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testReportMailIsNotSentToUsersThatDidNotActivateReport(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create([
            'report' => false,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        self::bootKernel();
        // Create a mock to intercept mails and assert that none is sent
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-report-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testReportMailContainsSpamMessages(): void
    {
        $domain = DomainFactory::createOne([
            'reportSpamLevel' => 2,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create([
            'report' => false,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $this->setupMail($addrS, $addrR, status: MessageStatus::SPAMMED);
        $this->setupMail($addrS, $addrR2, status: MessageStatus::UNTREATED);
        self::bootKernel();
        // Create a mock to intercept mails and assert only 1 mail sent to recipient1
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($recipient): bool {
                    $address = $email->getTo()[0]->getAddress();
                    return $address === $recipient->getEmail();
            }));
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-report-mail');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }
}
