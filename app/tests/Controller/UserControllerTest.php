<?php

namespace App\Tests\Controller;

use App\Entity\Domain;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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

    public function testAdminCannotListAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/admin/users/local');

        assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCannotCreateAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain, ['roles' => 'ROLE_ADMIN']);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        assertSame(403, $client->getResponse()->getStatusCode());
        assertSame($initialCount, UserFactory::count());
    }

    public function testAdminCannotEditAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain, [
                'fullname' => 'test',
                'username' => 'test',
                'roles' => '["ROLE_SUPER_ADMIN"]', // Try to escalate privileges
                'email' => 'test@' . $domain->getDomain(),
                'password' => [
                    'first' => 'secret',
                    'second' => 'secret',
                ],
                'domains' => [$domain->getId(), $otherDomain->getId()], // Try to get access to all domains
        ]);
        $url = '/admin/users/local/' . $admin->getId() . '/edit';
        $client->request(Request::METHOD_POST, $url, $payload);

        assertSame(403, $client->getResponse()->getStatusCode());
        assertSame($initialCount, UserFactory::count());
    }

    public function testAdminCannotChangeAdminPassword(): void
    {
        $client = static::createClient();
        $admin = UserFactory::new()->admin()->create();
        $otherAdmin = UserFactory::new()->admin()->create();
        $initialPassword = $admin->getPassword();
        $client->loginUser($admin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/local/' . $otherAdmin->getId() . '/changePassword';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                'password' => [
                    'first' => 'other', // Try to take control of admin modifying password
                    'second' => 'other',
                ],
                '_token' => $this->generateCsrfToken($client, 'user'),
            ],
        ]);

        assertSame(403, $client->getResponse()->getStatusCode());
        assertSame($initialCount, UserFactory::count());
        self::assertSame($initialCount, UserFactory::count());
        self::assertSame($initialPassword, $admin->getPassword());
    }

    /**
     * @param array<mixed>|null $attributes
     * @return array<string, array<string, mixed>>
     */
    private function createPayload(KernelBrowser $client, Domain $domain, ?array $attributes = null): array
    {
        $token = $this->generateCsrfToken($client, 'user');

        return [
            'user' => [
                'fullname' => $attributes['fullname'] ?? 'Test admin',
                'username' => $attributes['username'] ?? 'test',
                'roles' => $attributes['roles'] ?? '["ROLE_ADMIN"]',
                'email' => $attributes['email'] ?? 'test@' . $domain->getDomain(),
                'password' => [
                    'first' => $attributes['password']['first'] ?? 'secret',
                    'second' => $attributes['password']['second'] ?? 'secret',
                ],
                'domains' => $attributes['domains'] ?? [$domain->getId()],
                '_token' => $token,
            ],
        ];
    }
}
