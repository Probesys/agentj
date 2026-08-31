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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SenderRuleControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testUserCanBlockSenderForThemself(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);
        $csrfToken = $this->getCsrfToken($client, '/rules/new/B');

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

    public function testAdminCanAuthorizeSenderDomainForManagedDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $domainUser = UserFactory::findBy(['email' => '@' . $domain->getDomain()])[0];
        $admin = UserFactory::new()->admin()->create(['domains' => [$domain]]);
        $client->loginUser($admin);
        $csrfToken = $this->getCsrfToken($client, '/rules/new/W');

        $client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'trusted.example.org',
                'domain' => $domain->getId(),
            ],
        ]);

        self::assertResponseIsSuccessful();
        $senderRule = $this->getSenderRule($domainUser, '@trusted.example.org');
        self::assertSame('accept', $senderRule->getWbRule());
        self::assertSame(SenderRule::TYPE_ADMIN, $senderRule->getType());
        self::assertSame(5, $senderRule->getSenderRuleAddress()->getPriority());
    }

    public function testInvalidSenderAddressIsRejected(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $initialRuleAddressCount = RuleAddressFactory::count();
        $client->loginUser($user);

        $csrfToken = $this->getCsrfToken($client, '/rules/new/W');
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

    public function testAdminCannotCreateRuleForUnmanagedDomain(): void
    {
        $client = static::createClient();
        $managedDomain = DomainFactory::new()->create();
        $unmanagedDomain = DomainFactory::new()->create();
        $admin = UserFactory::new()->admin()->create(['domains' => [$managedDomain]]);
        $client->loginUser($admin);
        $csrfToken = $this->getCsrfToken($client, '/rules/new/W');
        $initialUserCount = UserFactory::count();

        $client->request(Request::METHOD_POST, '/rules/new/W', [
            'sender_rule' => [
                '_token' => $csrfToken,
                'email' => 'sender@example.org',
                'domain' => $unmanagedDomain->getId(),
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"status":"danger"', (string) $client->getResponse()->getContent());
        self::assertSame($initialUserCount, UserFactory::count());
    }

    public function testImportOnlyListsDomainsManagedByAdmin(): void
    {
        $client = static::createClient();
        $managedDomain = DomainFactory::createOne();
        $unmanagedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin()->create(['domains' => [$managedDomain]]);
        $client->loginUser($admin);

        $crawler = $client->request(Request::METHOD_POST, '/rules/admin/import/W');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter("option[value='{$managedDomain->getId()}']"));
        self::assertCount(0, $crawler->filter("option[value='{$unmanagedDomain->getId()}']"));
    }

    public function testDeletingUserRuleAlsoDeletesAliasRule(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        UserFactory::new()->user($domain)->create(['originalUser' => $user]);
        $service = static::getContainer()->get(SenderRuleService::class);
        self::assertTrue($service->createOrUpdateForUserAndAliases(
            'sender@example.org',
            $user,
            'accept',
            SenderRule::TYPE_USER,
        ));
        $ruleAddress = $this->getRuleAddressRepository()->findOneBy(['email' => 'sender@example.org']);
        self::assertNotNull($ruleAddress);
        $senderRuleRepository = static::getContainer()->get(SenderRuleRepository::class);
        self::assertSame(2, $senderRuleRepository->count([
            'senderRuleAddress' => $ruleAddress,
            'priority' => SenderRule::PRIORITY_USER,
        ]));

        $client->loginUser($user);
        $crawler = $client->request(Request::METHOD_GET, '/rules/W');
        $client->click($crawler->filter('a.confirmModal')->link());

        self::assertResponseRedirects();
        self::assertSame(0, $senderRuleRepository->count([
            'senderRuleAddress' => $ruleAddress,
            'priority' => SenderRule::PRIORITY_USER,
        ]));
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

    private function getRuleAddressRepository(): RuleAddressRepository
    {
        return static::getContainer()->get(RuleAddressRepository::class);
    }

    private function getCsrfToken(KernelBrowser $client, string $path): string
    {
        $crawler = $client->request(Request::METHOD_GET, $path);
        $token = $crawler->filter('input[name="sender_rule[_token]"]')->attr('value');
        self::assertNotNull($token);

        return $token;
    }
}
