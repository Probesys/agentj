<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Command\SendAuthMailRequestCommand;
use App\Entity\Message;
use App\Repository\DomainRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\CryptEncryptService;
use App\Service\LocaleService;
use App\Service\LogService;
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        // Generate a mail sent 6 hours ago, and whom the sender is unauthenticated
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($message, '-6 hours');
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

    public function testAuthMailIsNotSentSeveralTimesToSameSender(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 8 hours ago, and whom the sender is unauthenticated
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($message, '-8 hours');
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($message, '-6 hours');
        self::bootKernel();
        // Create a mock to intercept mails and assert 2 are sent to the sender
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly(1))
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

    public function testAuthMailIsNotSentIfSenderIsAMailingList(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago, and whom the sender is a mailing list (unauthenticated)
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $message->setIsMlist(true);
        $this->setMessageDate($message, '-6 hours');
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
        // Generate a mail sent 6 hours ago, but not yet processed by Amavis
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = $this->setupMail($addrS, $addrR);
        $message = MessageFactory::findBy(['mailId' => $message->getMailId()])[0];
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
        // Generate a mail sent 6 hours ago
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $message = $this->setupMail($addrS, $addrR);
        $message = MessageFactory::findBy(['mailId' => $message->getMailId()])[0];
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
        $message = $this->setupMail($addrS, $addrR);
        $this->setMessageDate($message, '-6 hours');
        // Create another message sent 6 hours ago and for which authentication mail has already been sent
        $otherMessage = $this->setupMail($addrS, $addrR, 'otherTest');
        $sixHoursAgo = new DateTimeImmutable("-6 hours")->getTimestamp();
        $this->setMessageDate($otherMessage, '-6 hours', ['sendCaptcha' => $sixHoursAgo]);
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

    public function testExecuteFailsWhenSendMailTokenLockCannotBeAcquired(): void
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
            ->with('msgs-send-mail-token', 1800)
            ->willReturn($lock);

        $command = new SendAuthMailRequestCommand(
            $this->createMock(DomainRepository::class),
            $this->createMock(MessageRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(CryptEncryptService::class),
            $this->createMock(MailerInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(LogService::class),
            $this->createMock(LocaleService::class),
            $lockFactory,
            'test-amavisd-release',
        );
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            "Can't acquire the msgs-send-mail-token lock, the command is probably already running.",
            $commandTester->getDisplay(),
        );
    }

    public function testExecuteFailsWhenSinceDaysParamNotValid(): void
    {
        $application = new Application(self::$kernel);
        $command = $application->find('agentj:send-auth-mail-token');
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            '--since-days' => -1,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            'Days must be greater or equal to 0',
            $commandTester->getDisplay(),
        );
    }

    /**
     * @param array<string, mixed>|null $attributes
     */
    private function setMessageDate(Message $message, ?string $delta = '-6 hours', ?array $attributes = []): void
    {
        $message->setSendCaptcha($attributes['sendCaptcha'] ?? 0);
        $message->setTimeNum(new DateTimeImmutable($delta)->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus(MessageStatus::UNTREATED);
        $this->entityManager->flush();
    }
}
