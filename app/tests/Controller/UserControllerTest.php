<?php

namespace App\Tests\Controller;

use App\Entity\SenderRule;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function PHPUnit\Framework\assertSame;

class UserControllerTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testUserCannotListUsers(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $client->request('GET', '/admin/users/email');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCannotListUsersIfNoDomain(): void
    {
        $client = static::createClient();
        $admin = UserFactory::new()->admin()->create([
            'domains' => [],
        ]);
        $client->loginUser($admin);

        $client->request('GET', '/admin/users/email');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListUsersIfNoDomain(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create([
            'domains' => [],
        ]);
        $client->loginUser($superAdmin);

        $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanListUsersOfItsDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($admin);
        $domainUsers = UserFactory::new()
            ->many(2)
            ->applyStateMethod('user')
            ->create([
                'domain' => $domain,
            ]);
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user()->create([
            'domain' => $otherDomain,
        ]);

        $crawler = $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
        $seenUsers = $crawler
            ->filter('td[data-title="Email"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(2, $seenUsers);
        foreach ($domainUsers as $user) {
            self::assertContains($user->getEmail(), $seenUsers);
        }
        self::assertNotContains($otherDomainUser->getEmail(), $seenUsers);
    }

    public function testSuperAdminCanListAllUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domainUsers = UserFactory::new()->many(2)
            ->applyStateMethod('user')
            ->create([
                'domain' => $domain,
            ]);
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user()->create([
            'domain' => $otherDomain,
        ]);

        $crawler = $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
        $seenUsers = $crawler
            ->filter('td[data-title="Email"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(3, $seenUsers);
        foreach ($domainUsers as $user) {
            self::assertContains($user->getEmail(), $seenUsers);
        }
        self::assertContains($otherDomainUser->getEmail(), $seenUsers);
    }

    public function testUserCannotCreateUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($user);

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@test.fr',
                'imapLogin' => 'testUser',
                'report' => true,
            ],
        ]);

        assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCanCreateUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        assertSame(200, $client->getResponse()->getStatusCode());
        // When creating a domain, a user is created for `@domain.tld` address.
        // So here, we have this user, plus the admin, plus the freshly created one.
        self::assertSame(3, UserFactory::count());
        $createdUser = UserFactory::findBy(['email' => 'test@' . $domain->getDomain()])[0];
        self::assertSame('Test User', $createdUser->getFullname());
        self::assertNull($createdUser->getImapLogin());
        self::assertTrue($createdUser->getReport());
    }

    public function testAdminCannotCreateUsersWithIncorrectCsrfToken(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => 'invalidCsrfToken',
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        assertSame(200, $client->getResponse()->getStatusCode()); // API always returns 200
        $response = $client->getResponse()->getContent();
        self::assertNotFalse($response);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"An error occurred when processing the form"}',
            $response,
        );
        // But no user created (1 because of `@domain.tld`, and one for the admin)
        self::assertSame(2, UserFactory::count());
    }

    public function testCreateAnAliasForAUserWithSenderRulesShouldApplyThoseRules(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $sender = UserFactory::new()->user($domain)->create();
        $recipient = UserFactory::new()->user($domain)->create();
        [$senderAddress, $userAddress] = $this->setupAddresses($sender, $recipient);
        $senderRuleAddress = RuleAddressFactory::new()->create([
            'email' => $senderAddress->getEmail(),
            'priority' => 7,
        ]);
        $senderRule = SenderRuleFactory::new()->create([
            'user' => $sender,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'accept',
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        self::assertCount(1, $sender->getSenderRules());
        $initialCount = UserFactory::count();
        // The rule is still associated to the user
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->refresh($recipient);

        $fullname = 'test fullname';
        $alias = 'other@' . $domain->getDomain();
        $username = $alias;
        $client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $sender->getId(),
                'report' => 1,
            ],
        ]);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"Added successfully!"}',
            $content,
        );
        // Alias has been created
        self::assertSame($initialCount + 1, UserFactory::count());
        $newAlias = UserFactory::last();
        self::assertSame($fullname, $newAlias->getFullname());
        self::assertSame($alias, $newAlias->getUsername());
        self::assertSame($alias, $newAlias->getEmail());
        // The rule has been associated to the new alias
        self::assertSame(1, $newAlias->getSenderRules()->count());
        $aliasSenderRule = $newAlias->getSenderRules()->first();
        self::assertNotFalse($aliasSenderRule);
        self::assertSame($newAlias->getId(), $aliasSenderRule->getUser()->getId());
        self::assertSame($senderRule->getSenderRuleAddress(), $aliasSenderRule->getSenderRuleAddress());
        self::assertSame($senderRule->getPriority(), $aliasSenderRule->getPriority());
        // The rule is still associated to the user
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->refresh($sender);
        self::assertSame(1, $sender->getSenderRules()->count());
        self::assertSame($senderRule, $sender->getSenderRules()->first());
    }
}
