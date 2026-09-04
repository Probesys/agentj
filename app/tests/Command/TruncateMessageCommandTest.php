<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\OutMessageFactory;
use App\Tests\Factory\UserFactory;
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
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testTruncateMessageCommandRemoveInMailsOlderThanDays(): void
    {
        $domain = DomainFactory::new()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Create 2 mails: 1 received 31 days ago, the other 10 days ago.
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::RESTORED);
        $mail = MessageFactory::findBy(['mailId' => $mailId])[0];
        $mail->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $mail2Id = $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        $mail2 = MessageFactory::findBy(['mailId' => $mail2Id])[0];
        $mail2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:truncate-message-since-days');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $mail = MessageFactory::findBy(['mailId' => $mailId]);
        self::assertCount(0, $mail);
        $mail2 = MessageFactory::findBy(['mailId' => $mail2Id]);
        self::assertCount(1, $mail2);
    }

    public function testTruncateMessageCommandRemoveOutMailsOlderThanDays(): void
    {
        $domain = DomainFactory::new()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Create 2 mails: 1 sent 31 days ago, the other 10 days ago.
        $mailId = $this->setupMail($addrS, $addrR, isInMessage: false, status: MessageStatus::RESTORED);
        $mail = OutMessageFactory::findBy(['mailId' => $mailId])[0];
        $mail->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $mail2Id = $this->setupMail($addrS, $addrR, isInMessage: false, status: MessageStatus::AUTHORIZED);
        $mail2 = OutMessageFactory::findBy(['mailId' => $mail2Id])[0];
        $mail2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('agentj:truncate-message-since-days');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $mail = OutMessageFactory::findBy(['mailId' => $mailId]);
        self::assertCount(0, $mail);
        $mail2 = OutMessageFactory::findBy(['mailId' => $mail2Id]);
        self::assertCount(1, $mail2);
    }
}
