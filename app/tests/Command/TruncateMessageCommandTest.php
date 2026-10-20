<?php

namespace App\Tests\Command;

use App\Amavis\MessageStatus;
use App\Tests\CommandHelper;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\OutMessageFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TruncateMessageCommandTest extends KernelTestCase
{
    use CommandHelper;
    use FactoryHelper;
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    public function testTruncateMessageCommandRemoveInMailsOlderThanDays(): void
    {
        $domain = DomainFactory::new()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        // Create 2 mails: 1 received 31 days ago, the other 10 days ago.
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::RESTORED);
        $message->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $this->save($message);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);
        $message2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $this->save($message2);

        self::executeCommand('agentj:truncate-message-since-days');

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
        $message = $this->setupMail($addrS, [$addrR], direction: 'outgoing', status: MessageStatus::RESTORED);
        $message->setTimeNum(new DateTimeImmutable('-31 days')->getTimestamp());
        $this->save($message);
        $message2 = $this->setupMail($addrS, [$addrR], direction: 'outgoing', status: MessageStatus::AUTHORIZED);
        $message2->setTimeNum(new DateTimeImmutable('-10 days')->getTimestamp());
        $this->save($message2);

        self::executeCommand('agentj:truncate-message-since-days');

        $mail = OutMessageFactory::findBy(['mailId' => $message->getMailId()]);
        self::assertCount(0, $mail);
        $mail2 = OutMessageFactory::findBy(['mailId' => $message2->getMailId()]);
        self::assertCount(1, $mail2);
    }
}
