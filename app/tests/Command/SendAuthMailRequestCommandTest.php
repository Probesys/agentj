<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Entity\Message;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendAuthMailRequestCommandTest extends KernelTestCase
{
    use CommandHelper;
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testAuthMailIsSentToNotAuthenticatedSender(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago, and whom the sender is unauthenticated
        $message = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message, '-6 hours');

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrS->getEmail());
    }

    public function testAuthMailIsNotSentIfAlreadySentSinceLessThanOneDayAgo(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message, '-6 hours');
        // Create another message sent 6 hours ago and for which authentication mail has already been sent
        $otherMessage = $this->setupMail($addrS, [$addrR]);
        $sixHoursAgo = new DateTimeImmutable("-6 hours")->getTimestamp();
        $this->setupMessageToAuthenticate($otherMessage, '-6 hours', messageAttributes: [
            'sendCaptcha' => $sixHoursAgo,
        ]);

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testAuthMailIsNotSentIfAlreadySentAndEnvelopeEmailIsDifferentThanMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago, and whom the sender has already received authentication mail
        $message = $this->setupMail($addrS, [$addrR]);
        $fromAddress = 'test@' . $domain->getDomain();
        $message->setFromAddr($fromAddress);
        $this->setupMessageToAuthenticate($message, '-6 hours', messageAttributes: [
            'sendCaptcha' => time() - 10,
        ]);
        // Generate a 2nd mail sent 6 hours ago, with same sender than for 1st authorized mail
        $message2 = $this->setupMail($addrS, [$addrR]);
        $message2->setFromAddr($fromAddress);
        $this->setupMessageToAuthenticate($message2, '-6 hours');

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testAuthMailIsNotSentSeveralTimesToSameSender(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 8 hours ago, and whom the sender is unauthenticated
        $message = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message, '-8 hours');
        $message2 = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message2, '-6 hours');

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertEmailAddressContains($mail, 'To', $addrS->getEmail());
    }

    public function testAuthMailIsNotSentIfSenderIsAMailingList(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago, and whom the sender is a mailing list (unauthenticated)
        $message = $this->setupMail($addrS, [$addrR]);
        $message->setIsMlist(true);
        $this->setupMessageToAuthenticate($message, '-6 hours');

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testAuthMailIsNotSentWhenMailIsNotYetProcessedByAmavis(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago, but not yet processed by Amavis
        $message = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message, '-6 hours', messageRecipientStatus: MessageStatus::UNRELEASED);

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testAuthMailIsNotSentWhenMailIsNotUntreated(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Generate a mail sent 6 hours ago
        $message = $this->setupMail($addrS, [$addrR]);
        $this->setupMessageToAuthenticate($message, '-6 hours', messageRecipientStatus: MessageStatus::RESTORED);

        $command = self::executeCommand('agentj:send-auth-mail-token');

        $command->assertCommandIsSuccessful();
        self::assertEmailCount(0);
    }

    public function testExecuteFailsWhenSinceDaysParamNotValid(): void
    {
        self::bootKernel();
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
     * @param array<string, mixed>|null $messageAttributes
     */
    private function setupMessageToAuthenticate(
        Message $message,
        ?string $delta = '-6 hours',
        ?int $messageRecipientStatus = MessageStatus::UNTREATED,
        ?array $messageAttributes = []
    ): void {
        $message->setSendCaptcha($messageAttributes['sendCaptcha'] ?? 0);
        $message->setTimeNum(new DateTimeImmutable($delta)->getTimestamp());
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient->setStatus($messageRecipientStatus);
        $this->save($messageRecipient);
    }
}
