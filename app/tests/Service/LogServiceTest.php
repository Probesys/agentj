<?php

namespace App\Tests\Service;

use App\Service\LogService;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LogFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LogServiceTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    public function testAddLogSavesTheGivenDomain(): void
    {
        $domain = DomainFactory::createOne();
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLog('restore', 'mail-id', 'details', $domain);

        $log = LogFactory::last();
        self::assertSame('restore', $log->getAction());
        self::assertSame('mail-id', $log->getMailId());
        self::assertSame($domain->getId(), $log->getDomain()?->getId());
    }

    public function testAddLogForRecipientSetsTheDomainOfTheRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLogForRecipient('restore', $messageRecipient);

        $log = LogFactory::last();
        self::assertSame($message->getMailId(), $log->getMailId());
        self::assertSame($domain->getId(), $log->getDomain()?->getId());
    }

    public function testAddLogForRecipientHasNoDomainIfTheDomainIsNotManaged(): void
    {
        $sender = UserFactory::new()->user(DomainFactory::createOne())->create();
        $recipient = UserFactory::new()->user(DomainFactory::createOne())->create();
        [$addrS] = $this->setupAddresses($sender, $recipient);
        $unmanagedAddress = AddressFactory::createOne([
            'domain' => 'org.unmanaged',
            'partitionTag' => 0,
            'email' => 'someone@unmanaged.org',
        ]);
        $message = $this->setupMail($addrS, [$unmanagedAddress]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLogForRecipient('restore', $messageRecipient);

        self::assertNull(LogFactory::last()->getDomain());
    }
}
