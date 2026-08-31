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

    public function testAuthMailIsSentToNotAuthenticatedSenderCaseInsensitive(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $senderAddressOtherCase = AddressFactory::createOne([
            'domain' => $senderAddress->getDomain(),
            'partitionTag' => 0,
            'email' => strtoupper($sender->getEmail()),
        ]);
        // Generate 2 mails sent 6 hours ago
        // and whom the sender is the same unauthenticated user
        // but with email addresses using different case.
        $mailId = $this->setupMail($senderAddress, $recipientAddress, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($mailId);
        $secondMailId = $this->setupMail($senderAddressOtherCase, $recipientAddress, status: MessageStatus::UNRELEASED);
        $this->setMessageDate($secondMailId);
        self::bootKernel();
        // Create a mock to intercept mails and assert exactly 2 are sent.
        // This behavior is not related to case.
        $mailer = $this->createMock(MailerInterface::class);
        $authMailRecipients = [
            $sender->getEmail(),
            $senderAddressOtherCase->getEmail(),
        ];
        $mailer->expects($this->exactly(2))
            ->method('send')
            ->with(
                $this->callback(function (Email $email) use ($authMailRecipients): bool {
                    return in_array($email->getTo()[0]->getAddress(), $authMailRecipients);
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
