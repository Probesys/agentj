<?php

namespace App\Tests\Controller;

use App\Entity\Address;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testAdminCanBatchDeleteSenderRulesInItsDomain(): void
    {
        $domain1 = DomainFactory::createOne();
        $user1 = UserFactory::new()->user($domain1)->create();
        $domain2 = DomainFactory::createOne();
        $user2 = UserFactory::new()->user($domain2)->create();
        $recipient = UserFactory::new()->user($domain1)->create();
        $admin = UserFactory::new()->admin([$domain1])->create();
        $this->client->loginUser($admin);
        [$addrSender1, $addrRecipient] = $this->setupAddresses($user1, $recipient);
        $addrSender2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($user2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $user2->getEmail(),
        ]);
        $senderRule1 = $this->setupSenderRule($addrSender1, $recipient);
        $senderRule2 = $this->setupSenderRule($addrSender2, $user2);
        $initialSenderRuleCount = SenderRuleFactory::count();

        $this->client->request(Request::METHOD_POST, '/rules/batch/delete', [
            'id' => [
                // [userId, senderRuleAddressId, priority]
                json_encode([
                    $recipient->getId(),
                    $senderRule1->getSenderRuleAddress()->getId(),
                    $senderRule1->getPriority(),
                ]),
                json_encode([
                    $user2->getId(),
                    $senderRule2->getSenderRuleAddress()->getId(),
                    $senderRule2->getPriority(),
                ]),
            ],
            '_token' => $this->generateCsrfToken($this->client, 'batch'),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame($initialSenderRuleCount - 1, SenderRuleFactory::count());
        // senderRule1 has been deleted
        self::assertSame(0, SenderRuleFactory::count([
            'user' => $recipient,
            'senderRuleAddress' => $senderRule1->getSenderRuleAddress(),
            'priority' => $senderRule1->getPriority(),
        ]));
        // senderRule2 has not been deleted (outside domain)
        self::assertSame(1, SenderRuleFactory::count([
            'user' => $user2,
            'senderRuleAddress' => $senderRule2->getSenderRuleAddress(),
            'priority' => $senderRule2->getPriority(),
        ]));
    }

    public function testAdminCannotBatchDeleteSenderRulesWithoutValidCsrfToken(): void
    {
        $domain = DomainFactory::createOne();
        $user1 = UserFactory::new()->user($domain)->create();
        $user2 = UserFactory::new()->user($domain)->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrSender1, $addrRecipient] = $this->setupAddresses($user1, $recipient);
        $addrSender2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($user2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $user2->getEmail(),
        ]);
        $senderRule1 = $this->setupSenderRule($addrSender1, $recipient);
        $senderRule2 = $this->setupSenderRule($addrSender2, $user2);
        $initialSenderRuleCount = SenderRuleFactory::count();

        $this->client->request(Request::METHOD_POST, '/rules/batch/delete', [
            'id' => [
                // [userId, senderRuleAddressId, priority]
                sprintf(
                    '[%d, %d, %d]',
                    $recipient->getId(),
                    $senderRule1->getSenderRuleAddress()->getId(),
                    $senderRule1->getPriority()
                ),
                sprintf(
                    '[%d, %d, %d]',
                    $user2->getId(),
                    $senderRule2->getSenderRuleAddress()->getId(),
                    $senderRule2->getPriority()
                ),
            ],
            '_token' => 'invalid CSRF token',
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        // senderRule1 has not been deleted (invalid CSRF)
        self::assertSame(1, SenderRuleFactory::count([
            'user' => $recipient,
            'senderRuleAddress' => $senderRule1->getSenderRuleAddress(),
            'priority' => $senderRule1->getPriority(),
        ]));
        // senderRule2 has not been deleted (invalid CSRF)
        self::assertSame(1, SenderRuleFactory::count([
            'user' => $user2,
            'senderRuleAddress' => $senderRule2->getSenderRuleAddress(),
            'priority' => $senderRule2->getPriority(),
        ]));
    }

    public function testAdminCanDeleteSenderRulesInItsDomain(): void
    {
        $domain1 = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain1)->create();
        $recipient = UserFactory::new()->user($domain1)->create();
        $admin = UserFactory::new()->admin([$domain1])->create();
        $this->client->loginUser($admin);
        [$addrSender, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $senderRule = $this->setupSenderRule($addrSender, $recipient);
        $initialSenderRuleCount = SenderRuleFactory::count();

        $url = sprintf(
            '/rules/%d/%d/%d/delete',
            $recipient->getId(),
            $senderRule->getSenderRuleAddress()->getId(),
            $senderRule->getPriority()
        );

        $csrfKey = 'delete' . $recipient->getId() . $senderRule->getSenderRuleAddress()->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, $csrfKey),
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialSenderRuleCount - 1, SenderRuleFactory::count());
        // senderRule1 has been deleted
        self::assertSame(0, SenderRuleFactory::count([
            'user' => $recipient,
            'senderRuleAddress' => $senderRule->getSenderRuleAddress(),
            'priority' => $senderRule->getPriority(),
        ]));
    }

    public function testAdminCannotDeleteSenderRulesWithoutValidCsrfToken(): void
    {
        $domain1 = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain1)->create();
        $recipient = UserFactory::new()->user($domain1)->create();
        $admin = UserFactory::new()->admin([$domain1])->create();
        $this->client->loginUser($admin);
        [$addrSender1, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $senderRule1 = $this->setupSenderRule($addrSender1, $recipient);
        $initialSenderRuleCount = SenderRuleFactory::count();

        $url = sprintf(
            '/rules/%d/%d/%d/delete',
            $recipient->getId(),
            $senderRule1->getSenderRuleAddress()->getId(),
            $senderRule1->getPriority()
        );
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'invalid CSRF token'),
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        // senderRule1 has not been deleted (invalid CSRF)
        self::assertSame(1, SenderRuleFactory::count([
            'user' => $recipient,
            'senderRuleAddress' => $senderRule1->getSenderRuleAddress(),
            'priority' => $senderRule1->getPriority(),
        ]));
    }

    public function testAdminCannotDeleteSenderRulesOutsideItsDomain(): void
    {
        $domain1 = DomainFactory::createOne();
        $domain2 = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain2)->create();
        $recipient = UserFactory::new()->user($domain2)->create();
        $admin = UserFactory::new()->admin([$domain1])->create();
        $this->client->loginUser($admin);
        [$addrSender, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $senderRule = $this->setupSenderRule($addrSender, $recipient);
        $initialSenderRuleCount = SenderRuleFactory::count();

        $url = sprintf(
            '/rules/%d/%d/%d/delete',
            $recipient->getId(),
            $senderRule->getSenderRuleAddress()->getId(),
            $senderRule->getPriority()
        );
        $csrfKey = 'delete' . $recipient->getId() . $senderRule->getSenderRuleAddress()->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, $csrfKey),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        // senderRule has not been deleted (outside domain)
        self::assertSame(1, SenderRuleFactory::count([
            'user' => $recipient,
            'senderRuleAddress' => $senderRule->getSenderRuleAddress(),
            'priority' => $senderRule->getPriority(),
        ]));
    }

    private function setupSenderRule(Address $senderAddress, User $recipient): SenderRule
    {
        $ruleAddress = RuleAddressFactory::new()->create([
            'priority' => 6,
            'email' => $senderAddress->getEmail(),
        ]);

        $senderRule = SenderRuleFactory::new()->create([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
            'wb' => 'accept',
            'priority' => 100,
        ]);

        return $senderRule;
    }
}
