<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function PHPUnit\Framework\assertSame;

class UserControllerTest extends WebTestCase
{
    use Factories;
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
        $user = UserFactory::new()->admin()->create([
            'domains' => [],
        ]);
        $client->loginUser($user);

        $client->request('GET', '/admin/users/email');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListUsersIfNoDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->superAdmin()->create([
            'domains' => [],
        ]);
        $client->loginUser($user);

        $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanListUsersOfItsDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($user);
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
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);
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
        $user = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($user);

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
        self::assertSame(2, UserFactory::count());
        $createdUser = UserFactory::findBy(['email' => 'test@' . $domain->getDomain()])[0];
        self::assertSame('Test User', $createdUser->getFullname());
        self::assertNull($createdUser->getImapLogin());
        self::assertTrue($createdUser->getReport());
    }

    public function testAdminCannotCreateUsersWithIncorrectCsrfToken(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($user);

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
        self::assertSame(1, UserFactory::count()); // But no user created
    }
}
