<?php

namespace App\Tests\Command;

use App\Entity\Alert;
use App\Entity\Domain;
use App\Entity\User;
use App\MessageHandler\CreateAlertMessageHandler;
use App\Tests\CommandHelper;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Util\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CreateAlertForBounceFloodCommandTest extends KernelTestCase
{
    use CommandHelper;
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    public function testAlertsAdminsWhenBouncesReachThreshold(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->receiveBounces($recipient, 3);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Bounce flood detected for ' . $recipient->getEmail(),
            $applicationTester->getDisplay()
        );
        $this->assertSame(1, $this->countBounceFloodAlerts($admin, $recipient));
        $this->assertSame(1, $this->countBounceFloodAlerts($superAdmin, $recipient));
    }

    public function testSendsAnEmailToTheAdmin(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create(['domain' => $domain]);
        $this->receiveBounces($recipient, 3);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertNotNull($email);
        $this->assertEmailAddressContains($email, 'To', (string) $admin->getEmail());
        $this->assertEmailTextBodyContains($email, (string) $recipient->getEmail());
        $this->assertEmailTextBodyContains($email, '3');
    }

    public function testCountsAutoRepliesSentWithAnEmptyEnvelopeSender(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->receiveBounces($recipient, 3, fromAddr: 'someone@example.org');

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(1, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testDoesNotAlertBelowThreshold(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->receiveBounces($recipient, 2);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testDoesNotCountNormalMessages(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $sender = AddressFactory::createOne(['email' => 'friend@example.org']);
        $recipientAddress = $this->recipientAddress($recipient);
        for ($i = 0; $i < 3; $i++) {
            $this->setupMail($sender, [$recipientAddress]);
        }

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testDoesNotCountBouncesOlderThanOneHour(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->receiveBounces($recipient, 3, timeNum: time() - 2 * 3600);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testDoesNothingIfThresholdIsZero(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 0]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->receiveBounces($recipient, 50);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testDoesNotAlertAdminsOfOtherDomains(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherAdmin = UserFactory::new()->admin([$otherDomain])->create();
        $this->receiveBounces($recipient, 3);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($otherAdmin, $recipient));
    }

    public function testDoesNotRepeatTheAlert(): void
    {
        $domain = DomainFactory::createOne(['bounceFloodThreshold' => 3]);
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->receiveBounces($recipient, 3);

        self::executeCommand('app:create-alert-for-bounce-flood')->assertCommandIsSuccessful();
        $this->receiveBounces($recipient, 3);
        self::executeCommand('app:create-alert-for-bounce-flood')->assertCommandIsSuccessful();

        $this->assertSame(1, $this->countBounceFloodAlerts($admin, $recipient));
    }

    public function testAlertsTheUserIfUserAlertsAreEnabled(): void
    {
        $domain = DomainFactory::createOne([
            'bounceFloodThreshold' => 3,
            'sendUserAlerts' => true,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $this->receiveBounces($recipient, 3);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(1, $this->countBounceFloodAlerts($recipient, $recipient));
    }

    public function testDoesNotAlertTheUserIfUserAlertsAreDisabled(): void
    {
        $domain = DomainFactory::createOne([
            'bounceFloodThreshold' => 3,
            'sendUserAlerts' => false,
            'sendUserMailAlerts' => false,
        ]);
        $recipient = UserFactory::new()->user($domain)->create();
        $this->receiveBounces($recipient, 3);

        $applicationTester = self::executeCommand('app:create-alert-for-bounce-flood');

        $applicationTester->assertCommandIsSuccessful();
        $this->assertSame(0, $this->countBounceFloodAlerts($recipient, $recipient));
    }

    private function receiveBounces(
        User $recipient,
        int $count,
        string $fromAddr = 'MAILER-DAEMON@mx.example.org',
        ?int $timeNum = null,
    ): void {
        $emptySender = AddressFactory::findOrCreate([
            'partitionTag' => 0,
            'email' => '',
            'domain' => '',
        ]);
        $recipientAddress = $this->recipientAddress($recipient);

        for ($i = 0; $i < $count; $i++) {
            $this->setupMail(
                $emptySender,
                [$recipientAddress],
                fromAddr: $fromAddr,
                subject: 'Mail delivery failed: returning message to sender',
                messageAttributes: ['timeNum' => $timeNum ?? time() - 60],
            );
        }
    }

    private function recipientAddress(User $recipient): \App\Entity\Address
    {
        /** @var Domain $domain */
        $domain = $recipient->getDomain();

        return AddressFactory::findOrCreate([
            'partitionTag' => 0,
            'email' => $recipient->getEmail(),
            'domain' => Url::reverseDomainName((string) $domain->getDomain()),
        ]);
    }

    private function countBounceFloodAlerts(User $alertedUser, User $mailbox): int
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(Alert::class)->count([
            'alertType' => CreateAlertMessageHandler::TYPE_BOUNCE_FLOOD,
            'user' => $alertedUser,
            'refUser' => strtolower((string) $mailbox->getEmail()),
        ]);
    }
}
