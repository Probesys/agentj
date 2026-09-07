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
        $recipient2 = UserFactory::new()->user($domain)->create();
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
        // Create a mock to intercept mails and assert 2 mails are sent, 1 for each recipient.
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly(2))
            ->method('send')
            ->with(
                $this->callback(function (Email $email) use ($recipient, $recipient2): bool {
                    $address = $email->getTo()[0]->getAddress();

                    return $address === $recipient->getEmail()
                        || $address === $recipient2->getEmail();
                })
            );
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
}
