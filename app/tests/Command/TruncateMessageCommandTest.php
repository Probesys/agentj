<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\OutMessageFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TruncateMessageCommandTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testTruncateMessageCommandRemoveInMailsOlderThanDays(): void
    {
        $domain = DomainFactory::new()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Create 2 mails: 1 received 31 days ago, the other 10 days ago.
        $message = $this->setupMail($addrS, $addrR, status: MessageStatus::RESTORED);
        $message->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $message2 = $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        $message2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $this->entityManager->flush();

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:truncate-message-since-days');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $mail = MessageFactory::findBy(['mailId' => $message->getMailId()]);
        self::assertCount(0, $mail);
        $mail2 = MessageFactory::findBy(['mailId' => $message2->getMailId()]);
        self::assertCount(1, $mail2);
    }

    public function testTruncateMessageCommandRemoveOutMailsOlderThanDays(): void
    {
        $domain = DomainFactory::new()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Create 2 mails: 1 sent 31 days ago, the other 10 days ago.
        $message = $this->setupMail($addrS, $addrR, isInMessage: false, status: MessageStatus::RESTORED);
        $message->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $message2 = $this->setupMail($addrS, $addrR, isInMessage: false, status: MessageStatus::AUTHORIZED);
        $message2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $this->entityManager->flush();

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:truncate-message-since-days');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $mail = OutMessageFactory::findBy(['mailId' => $message->getMailId()]);
        self::assertCount(0, $mail);
        $mail2 = OutMessageFactory::findBy(['mailId' => $message2->getMailId()]);
        self::assertCount(1, $mail2);
    }
}
