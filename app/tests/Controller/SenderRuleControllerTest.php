<?php

namespace App\Tests\Controller;

use App\Entity\SenderRule;
use App\Entity\User;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Service\SenderRuleService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    public function testPostCreateSenderRuleCreatesRuleForUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($client, 'sender_rule');
        $client->request(Request::METHOD_POST, '/rules/new/B', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'blocked@example.org',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The address is now banned"}',
            (string) $client->getResponse()->getContent(),
        );

        $senderRule = $this->getSenderRule($user, 'blocked@example.org');
        self::assertSame('block', $senderRule->getWbRule());
        self::assertSame(SenderRule::TYPE_USER, $senderRule->getType());
        self::assertSame(SenderRule::PRIORITY_USER, $senderRule->getPriority());
    }

    public function testPostCreateSenderRuleFailsForAdmins(): void
    {
        $client = static::createClient();
        $admin = UserFactory::new()->admin()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $client->loginUser($admin);

        $csrfToken = $this->generateCsrfToken($client, 'sender_rule');
        $client->request(Request::METHOD_POST, '/rules/new/W', [
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
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($client, 'sender_rule');
        $client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'not an address',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"status":"danger"', (string) $client->getResponse()->getContent());
        self::assertSame($initialRuleAddressCount, RuleAddressFactory::count());
    }

    public function testGetImportOnlyListsDomainsManagedByAdmin(): void
    {
        $client = static::createClient();
        $managedDomain = DomainFactory::createOne();
        $unmanagedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        $client->loginUser($admin);

        $crawler = $client->request(Request::METHOD_POST, '/rules/admin/import/W');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter("option[value='{$managedDomain->getId()}']"));
        self::assertCount(0, $crawler->filter("option[value='{$unmanagedDomain->getId()}']"));
    }

    public function testPostDeleteDeletesRulesForUserAndHisAliases(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $client->loginUser($user);
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
        $csrfToken = $this->generateCsrfToken($client, "delete_sender_rule{$recipientId}{$senderId}");
        $client->request(Request::METHOD_GET, "/rules/{$recipientId}/{$senderId}/{$priority}/delete", [
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
