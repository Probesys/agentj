<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendAuthMailRequestCommandTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAuthMailIsSentToNotAuthenticatedSender(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        // Generate a mail sent 6 hours ago, and whom the sender is unauthenticated
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $em->flush();
        self::bootKernel();
        // Create a mock to intercept mails and assert 1 is sent to the sender
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (Email $email) use ($sender): bool {
                    return $email->getTo()[0]->getAddress() === $sender->getEmail();
                })
            );
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testAuthMailIsSentToNotAuthenticatedSenderSeveralTimes(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 8 hours ago, and whom the sender is unauthenticated
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($mailId, '-8 hours');
        // Generate a mail sent 6 hours ago, and whom the sender is unauthenticated
        $senderAddressOtherCase = AddressFactory::createOne([
            'domain' => $addrS->getDomain(),
            'partitionTag' => 0,
            'email' => strtoupper($sender->getEmail()),
        ]);
        $mailId = $this->setupMail($senderAddressOtherCase, $addrR, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($mailId, '-6 hours');
        self::bootKernel();
        // Create a mock to intercept mails and assert 2 are sent to the sender
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly(2))
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testAuthMailIsNotSentIfSenderIsAMailingList(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        // Generate a mail sent 6 hours ago, and whom the sender is a mailing list (unauthenticated)
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $message->setIsMlist(true);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $em->flush();
        self::bootKernel();
        // Create a mock to intercept mails and assert none is sent
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testAuthMailIsNotSentWhenMailIsNotYetProcessedByAmavis(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR);
        // Generate a mail sent 6 hours ago, but not yet processed by Amavis
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $em->flush();
        self::bootKernel();
        // Create a mock to intercept mails and assert none is sent
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testAuthMailIsNotSentWhenMailIsNotUntreated(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR);
        // Generate a mail sent 6 hours ago
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::RESTORED);
        $em->flush();
        self::bootKernel();
        // Create a mock to intercept mails and assert none is sent
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testAuthMailIsNotSentIfAlreadySentSinceLessThanOneDayAgo(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR);
        $otherMailId = $this->setupMail($addrS, $addrR, subject: 'otherTest');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        // Create another message sent 6 hours ago and for which authentication mail has already been sent
        $otherMessage = MessageFactory::findBy(['mailId' => $otherMailId])[0];
        $otherMessage->setSendCaptcha(new DateTimeImmutable("-6 hours")->getTimestamp());
        $otherMessage->setTimeNum(new DateTimeImmutable("-6 hours")->getTimestamp());
        $otherMessageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($otherMessageRecipient);
        $otherMessageRecipient->setStatus(MessageStatus::UNTREATED);
        $em->flush();
        self::bootKernel();
        // Create a mock to intercept mails and assert none is sent
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    private function setMessageDate(string $mailId, ?string $delta = '-6 hours'): void
    {
        $message = MessageFactory::findBy(['mailId' => $mailId])[0];
        $message->setSendCaptcha(0);
        $message->setTimeNum(new DateTimeImmutable($delta)->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $this->entityManager->flush();
    }
}
