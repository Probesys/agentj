<?php

namespace App\Tests\Service;

use App\Model\LogAction;
use App\Service\LogService;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LogFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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

        $logService->addLog(LogAction::Restore, 'mail-id', 'details', $domain);

        $log = LogFactory::last();
        self::assertSame(LogAction::Restore->value, $log->getAction());
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

        $logService->addLogForRecipient(LogAction::Restore, $messageRecipient);

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

        $logService->addLogForRecipient(LogAction::Restore, $messageRecipient);

        self::assertNull(LogFactory::last()->getDomain());
    }

    public function testAddLogSavesTheLoggedInUserAsAuthor(): void
    {
        $user = UserFactory::new()->user(DomainFactory::createOne())->create();
        static::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles()),
        );
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLog(LogAction::Restore, 'mail-id');

        self::assertSame($user->getId(), LogFactory::last()->getCreatedBy()?->getId());
    }

    public function testAddLogSavesTheImpersonatingAdminAsAuthor(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $user = UserFactory::new()->user($domain)->create();
        $originalToken = new UsernamePasswordToken($admin, 'main', $admin->getRoles());
        static::getContainer()->get(TokenStorageInterface::class)->setToken(
            new SwitchUserToken($user, 'main', $user->getRoles(), $originalToken),
        );
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLog(LogAction::Restore, 'mail-id');

        self::assertSame($admin->getId(), LogFactory::last()->getCreatedBy()?->getId());
    }

    public function testAddLogHasNoAuthorWithoutLoggedInUser(): void
    {
        $logService = static::getContainer()->get(LogService::class);

        $logService->addLog(LogAction::Restore, 'mail-id');

        self::assertNull(LogFactory::last()->getCreatedBy());
    }
}
