<?php

namespace App\Tests\Controller;

use App\Entity\Address;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Model\LogAction;
use App\Repository\LogRepository;
use App\Service\SenderRuleService;
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
    public function testPostCreateSenderRuleCreatesRuleForUser(): void
    {
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($this->client, 'sender_rule');
        $this->client->request(Request::METHOD_POST, '/rules/new/B', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'blocked@example.org',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The address is now banned"}',
            (string) $this->client->getResponse()->getContent(),
        );

        $senderRule = $this->getSenderRule($user, 'blocked@example.org');
        self::assertSame('block', $senderRule->getWbRule());
        self::assertSame(SenderRule::TYPE_USER, $senderRule->getType());
        self::assertSame(SenderRule::PRIORITY_USER, $senderRule->getPriority());
    }

    public function testPostCreateSenderRuleFailsForAdmins(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $this->client->loginUser($admin);

        $csrfToken = $this->generateCsrfToken($this->client, 'sender_rule');
        $this->client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'trusted.example.org',
            ],
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialRuleAddressCount, RuleAddressFactory::count());
    }

    public function testPostCreateSenderRuleFailsIfSenderAddressIsInvalid(): void
    {
        $user = UserFactory::new()->user()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $this->client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($this->client, 'sender_rule');
        $this->client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'not an address',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"status":"danger"', (string) $this->client->getResponse()->getContent());
        self::assertSame($initialRuleAddressCount, RuleAddressFactory::count());
    }

    public function testPostCreateSenderRuleFailsIfSenderAddressIsADomain(): void
    {
        $user = UserFactory::new()->user()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $this->client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($this->client, 'sender_rule');
        $this->client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'example.org',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"status":"danger"', (string) $this->client->getResponse()->getContent());
        self::assertSame($initialRuleAddressCount, RuleAddressFactory::count());
    }

    public function testPostImportSenderRuleActionOnlyListsDomainsManagedByAdmin(): void
    {
        $managedDomain = DomainFactory::createOne();
        $unmanagedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        $this->client->loginUser($admin);

        $crawler = $this->client->request(Request::METHOD_POST, '/rules/admin/import/W');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter("option[value='{$managedDomain->getId()}']"));
        self::assertCount(0, $crawler->filter("option[value='{$unmanagedDomain->getId()}']"));
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

    public function testPostBatchDeleteActionLogsTheDomainOfTheUser(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);
        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $user,
            'accept',
            SenderRule::TYPE_USER,
        ));
        $ruleAddress = RuleAddressFactory::find(['email' => 'sender@example.org']);

        $this->client->request(Request::METHOD_POST, '/rules/batch/delete', [
            'id' => [json_encode([$user->getId(), $ruleAddress->getId(), SenderRule::PRIORITY_USER])],
            '_token' => $this->generateCsrfToken($this->client, 'batch'),
        ]);

        self::assertResponseRedirects();
        $log = static::getContainer()->get(LogRepository::class)->findOneBy([
            'action' => LogAction::DeleteBatchSenderRule->value,
        ]);
        self::assertNotNull($log);
        self::assertSame($domain->getId(), $log->getDomain()?->getId());
    }

    public function testPostDeleteActionDeletesRulesForUserAndAliases(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);
        $alias = UserFactory::new()->alias($user)->create();
        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $user,
            'accept',
            SenderRule::TYPE_USER,
        ));
        $ruleAddress = RuleAddressFactory::find(['email' => 'sender@example.org']);
        SenderRuleFactory::assert()->exists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
        ]);
        SenderRuleFactory::assert()->exists([
            'user' => $alias,
            'senderRuleAddress' => $ruleAddress,
        ]);

        $recipientId = $user->getId();
        $senderId = $ruleAddress->getId();
        $priority = SenderRule::PRIORITY_USER;
        $csrfToken = $this->generateCsrfToken($this->client, "delete{$recipientId}{$senderId}");
        $this->client->request(Request::METHOD_POST, "/rules/{$recipientId}/{$senderId}/{$priority}/delete", [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects();
        SenderRuleFactory::assert()->notExists([
            'user' => $user,
            'senderRuleAddress' => $ruleAddress,
        ]);
        SenderRuleFactory::assert()->notExists([
            'user' => $alias,
            'senderRuleAddress' => $ruleAddress,
        ]);
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

    private function getSenderRule(User $recipient, string $address): SenderRule
    {
        $ruleAddress = RuleAddressFactory::findBy(['email' => $address])[0];

        $senderRule = SenderRuleFactory::findBy([
            'user' => $recipient,
            'senderRuleAddress' => $ruleAddress,
        ])[0];

        return $senderRule;
    }
}
