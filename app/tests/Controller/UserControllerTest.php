<?php

namespace App\Tests\Controller;

use App\Entity\Domain;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
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
    use FactoryHelper;
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
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin);

        $client->request('GET', '/admin/users/email');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListUsersIfNoDomain(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);

        $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanListUsersOfItsDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $user1 = UserFactory::new()->user($domain)->create();
        $user2 = UserFactory::new()->user($domain)->create();
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user($otherDomain)->create();

        $crawler = $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
        $seenUsers = $crawler
            ->filter('td[data-title="Email"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(2, $seenUsers);
        foreach ([$user1, $user2] as $admin) {
            self::assertContains($admin->getEmail(), $seenUsers);
        }
        self::assertNotContains($otherDomainUser->getEmail(), $seenUsers);
    }

    public function testSuperAdminCanListAllUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domainUser1 = UserFactory::new()->user($domain)->create();
        $domainUser2 = UserFactory::new()->user($domain)->create();
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user($otherDomain)->create();

        $crawler = $client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
        $seenUsers = $crawler
            ->filter('td[data-title="Email"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(3, $seenUsers);
        self::assertContains($domainUser1->getEmail(), $seenUsers);
        self::assertContains($domainUser2->getEmail(), $seenUsers);
        self::assertContains($otherDomainUser->getEmail(), $seenUsers);
    }

    public function testUserCannotCreateUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
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

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCanCreateUsersInItsDomains(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
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

        self::assertSame(200, $client->getResponse()->getStatusCode());
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
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        self::assertEquals(2, UserFactory::count()); // 1 admin user, and 1 user for domain rule

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => 'invalidCsrfToken',
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode()); // API always returns 200
        $response = $client->getResponse()->getContent();
        self::assertNotFalse($response);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"An error occurred when processing the form"}',
            $response,
        );
        // But no user created (1 because of `@domain.tld`, and 1 for the admin)
        self::assertSame(2, UserFactory::count());
    }

    public function testAdminCannotCreateUsersOutsideItsDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $otherDomain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode()); // API always returns 200
        self::assertSame($initialCount, UserFactory::count()); // But no user created
    }

    public function testSuperAdminCanCreateUsersInAllDomains(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount + 1, UserFactory::count());
        $createdUser = UserFactory::findBy(['email' => 'test@' . $domain->getDomain()])[0];
        self::assertSame('Test User', $createdUser->getFullname());
        self::assertNull($createdUser->getImapLogin());
        self::assertTrue($createdUser->getReport());
    }

    public function testSuperAdminCannotCreateUsersWithEmptyForm(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", []);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCannotCreateUserForANonExistingDomain(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@nonExistingDomain.tld',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateUserWithAnAlreadyUsedEmail(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => $user->getEmail(),
                'report' => true,
            ],
        ]);

        assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateUserThatIsPresentInImap(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
            'imapLogin' => 'test',
        ]);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'Test User',
                'email' => 'other@' . $domain->getDomain(),
                'imapLogin' => 'test',
                'report' => true,
            ],
        ]);

        assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message": "This IMAP login already exists. You can\'t use it"}',
            $content,
        );
    }

    public function testAdminCanEditUsersInItsDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $group = GroupFactory::new()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@' . $domain->getDomain();
        self::assertCount(0, $user->getGroups());
        $client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
                'groups' => [
                    $group->getId(),
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        self::assertSame($newFullname, $user->getFullname());
        self::assertSame($newEmail, $user->getEmail());
        self::assertCount(1, $user->getGroups());
    }

    public function testSuperAdminCanEditUsers(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@' . $domain->getDomain();
        $client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        self::assertSame($newFullname, $user->getFullname());
        self::assertSame($newEmail, $user->getEmail());
    }

    public function testSuperAdminCannotEditUsersWithANotExistingDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $newFullname,
                'email' => 'other@not-existing.tld',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
    }

    public function testSuperAdminCannotEditUsersWithAnAlreadyUsedMail(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $otherUser = UserFactory::new()->user($domain)->create([
            'email' => 'otherTest@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = $otherUser->getEmail();
        $client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
        $this->refresh($user);
        self::assertNotSame($newFullname, $user->getFullname());
        self::assertNotSame($newEmail, $user->getEmail());
    }

    public function testSuperAdminCannotEditUserThatIsAlreadyPresentInDestinationDomainImap(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        UserFactory::new()->user($otherDomain)->create([
            'email' => 'test@' . $otherDomain->getDomain(),
            'imapLogin' => 'test'
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@' . $otherDomain->getDomain();
        $client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
                'imapLogin' => 'test',
            ],
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This IMAP login already exists. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCanListAllAliases(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $alias = UserFactory::new()->alias($user)->create();

        $crawler = $client->request('GET', '/admin/users/alias');

        self::assertResponseIsSuccessful();
        $seenAliases = $crawler
            ->filter('td[data-title="Alias"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(1, $seenAliases);
        self::assertContains($alias->getEmail(), $seenAliases);
    }

    public function testSuperAdminCanCreateAnAlias(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $email = 'test@' . $domain->getDomain();
        $user = UserFactory::new()->user($domain)->create([
            'email' => $email,
        ]);
        $initialCount = UserFactory::count();

        $fullname = 'test fullname';
        $username = $user->getEmail();
        $alias = 'other@' . $domain->getDomain();
        $client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $user->getId(),
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
        self::assertSame($initialCount + 1, UserFactory::count());
        $newAlias = UserFactory::last();
        self::assertSame($fullname, $newAlias->getFullname());
        self::assertSame($alias, $newAlias->getUsername());
        self::assertSame($alias, $newAlias->getEmail());
        $this->refresh($user);
        self::assertCount(1, $user->getAliases());
    }

    public function testSuperAdminCannotCreateAnAliasForNotExistingDomain(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $email = 'test@' . $domain->getDomain();
        $user = UserFactory::new()->user($domain)->create([
            'email' => $email,
        ]);
        $initialCount = UserFactory::count();

        $fullname = 'test fullname';
        $username = $user->getEmail();
        $alias = 'other@other-domain.tld';
        $client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCannotCreateAlreadyExistingAlias(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $aliasEmail = 'alias@' . $domain->getDomain();
        $alias = UserFactory::new()->user($domain)->create([
            'email' => $aliasEmail,
            'originalUser' => $user,
        ]);
        $initialCount = UserFactory::count();

        $client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $alias->getEmail(),
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This alias is already used. You can\'t use it"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCanEditAlias(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $aliasEmail = 'alias@' . $domain->getDomain();
        $alias = UserFactory::new()->user($domain)->create([
            'email' => $aliasEmail,
            'originalUser' => $user,
        ]);
        $initialCount = UserFactory::count();

        $url = '/admin/users/alias/' . $alias->getId() . '/edit';
        $newEmail = 'newEmail@' . $domain->getDomain();
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $newEmail,
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
        self::assertSame($newEmail, $alias->getEmail());
        self::assertSame($newEmail, $alias->getUsername());
    }

    public function testSuperAdminCannotEditAlreadyExistingAlias(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $aliasEmail = 'alias@' . $domain->getDomain();
        $alias = UserFactory::new()->user($domain)->create([
            'email' => $aliasEmail,
            'originalUser' => $user,
        ]);
        $otherEmail = 'other@' . $domain->getDomain();
        $otherAlias = UserFactory::new()->alias($user)->create([
            'email' => $otherEmail,
        ]);
        $initialCount = UserFactory::count();

        $url = '/admin/users/alias/' . $alias->getId() . '/edit';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                '_token' => $this->generateCsrfToken($client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $otherAlias->getEmail(),
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testUserCannotListAdmins(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/admin/users/local');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListAllAdmins(): void
    {
        $client = static::createClient();
        $domain1 = DomainFactory::createOne();
        $admin1 = UserFactory::new()->admin([$domain1])->create();
        $domain2 = DomainFactory::createOne();
        $admin2 = UserFactory::new()->admin([$domain2])->create();
        $domain3 = DomainFactory::createOne();
        $admin3 = UserFactory::new()->admin([$domain3])->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);

        $crawler = $client->request(Request::METHOD_GET, '/admin/users/local');

        self::assertResponseIsSuccessful();
        $seenAdmins = $crawler
            ->filter('td[data-title="Login"]')
            ->each(fn ($node) => trim($node->text()))
        ;
        self::assertContains($admin1->getUsername(), $seenAdmins);
        self::assertContains($admin2->getUsername(), $seenAdmins);
        self::assertContains($admin3->getUsername(), $seenAdmins);
    }

    public function testUserCannotCreateAdmins(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $client->request(Request::METHOD_POST, '/admin/users/local/new');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    // Route will be reserved to super admins.
    // See https://github.com/Probesys/agentj-security/pull/5
    // TODO: when merged, modify this test to assert an admin cannot create admins.
    public function testAdminCanCreateAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertSame($initialCount + 1, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"Added successfully!"}',
            $content,
        );
        $createdAdmin = UserFactory::last();
        self::assertSame($payload['user']['fullname'], $createdAdmin->getFullname());
        self::assertSame($payload['user']['username'], $createdAdmin->getUsername());
        self::assertSame($payload['user']['email'], $createdAdmin->getEmail());
        $domains = $createdAdmin
            ->getDomains()
            ->map(fn(Domain $domain) => $domain->getId())
            ->toArray()
        ;
        self::assertSame([$domain->getId()], $domains);
    }

    public function testSuperAdminCanCreateAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount + 1, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"Added successfully!"}',
            $content,
        );
        $createdAdmin = UserFactory::last();
        self::assertSame($payload['user']['fullname'], $createdAdmin->getFullname());
        self::assertSame($payload['user']['username'], $createdAdmin->getUsername());
        self::assertSame($payload['user']['email'], $createdAdmin->getEmail());
        $domains = $createdAdmin
            ->getDomains()
            ->map(fn(Domain $domain) => $domain->getId())
            ->toArray()
        ;
        self::assertSame([$domain->getId()], $domains);
    }

    public function testSuperAdminCanCreateSuperAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $domains = array_map(fn($domain) => $domain->getId(), DomainFactory::all());
        $payload = $this->createPayload($client, $domain, [
            'domains' => $domains,
            'roles' => '["ROLE_SUPER_ADMIN"]',
        ]);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount + 1, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"Added successfully!"}',
            $content,
        );
        $createdAdmin = UserFactory::last();
        self::assertSame($payload['user']['fullname'], $createdAdmin->getFullname());
        self::assertSame($payload['user']['username'], $createdAdmin->getUsername());
        self::assertSame($payload['user']['email'], $createdAdmin->getEmail());
        $createdAdminDomains = $createdAdmin
            ->getDomains()
            ->map(fn(Domain $domain) => $domain->getId())
            ->toArray()
        ;
        $domains = array_map(fn($domain) => $domain->getId(), DomainFactory::all());
        self::assertSame($domains, $createdAdminDomains);
    }

    public function testSuperAdminCannotCreateAdminWithAlreadyUsedUsername(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        UserFactory::new()->user()->create(['username' => 'test']);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This login already exists. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateAdminWithAlreadyUsedEmail(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $email = 'test@test.fr';
        UserFactory::new()->user()->create(['email' => $email]);
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain, [
            'email' => $email,
        ]);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateAdminWithNotIdenticalPasswords(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($client, $domain, [
            'password' => [
                'second' => 'other',
            ],
        ]);
        $client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"Password confirmation is not valid"}',
            $content,
        );
    }

    public function testSuperAdminCanEditAdminsToSuperAdmins(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $newFullname = 'other';
        $newUsername = 'other';
        $newEmail = 'other@' . $domain->getDomain();
        $url = '/admin/users/local/' . $admin->getId() . '/edit';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                'email' => $newEmail,
                'fullname' => $newFullname,
                'username' => $newUsername,
                'roles' => '["ROLE_SUPER_ADMIN"]',
                'domains' => [],
                '_token' => $this->generateCsrfToken($client, 'user'),
            ]
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        self::assertSame($newFullname, $admin->getFullname());
        self::assertSame($newUsername, $admin->getUsername());
        self::assertSame($newEmail, $admin->getEmail());
        self::assertSame(["ROLE_SUPER_ADMIN"], $admin->getRoles());
    }

    public function testSuperAdminCannotEditAdminsIfUsernameAlreadyUsed(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        UserFactory::new()->user()->create(['username' => 'other']);
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $newFullname = 'other';
        $newUsername = 'other';
        $newEmail = 'other@' . $domain->getDomain();
        $url = '/admin/users/local/' . $admin->getId() . '/edit';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                'email' => $newEmail,
                'fullname' => $newFullname,
                'username' => $newUsername,
                'roles' => '["ROLE_SUPER_ADMIN"]',
                'domains' => [],
                '_token' => $this->generateCsrfToken($client, 'user'),
            ]
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This login already exists. You can\'t use it"}',
            $content,
        );
        $this->refresh($admin);
        self::assertNotSame($newFullname, $admin->getFullname());
        self::assertNotSame($newUsername, $admin->getUsername());
        self::assertNotSame($newEmail, $admin->getEmail());
    }

    public function testSuperAdminCanChangeAdminPassword(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $initialPassword = $admin->getPassword();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/local/' . $admin->getId() . '/changePassword';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                'password' => [
                    'first' => 'secret',
                    'second' => 'secret',
                ],
                '_token' => $this->generateCsrfToken($client, 'user'),
            ],
        ]);

        self::assertResponseRedirects('/admin/users/local?id=' . $admin->getId());
        self::assertSame($initialCount, UserFactory::count());
        self::assertNotEquals($initialPassword, $admin->getPassword());
    }

    public function testSuperAdminCannotChangeAdminPasswordIfPasswordsDoNotMatch(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $initialPassword = $admin->getPassword();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/local/' . $admin->getId() . '/changePassword';
        $client->request(Request::METHOD_POST, $url, [
            'user' => [
                'password' => [
                    'first' => 'secret',
                    'second' => 'other',
                ],
                '_token' => $this->generateCsrfToken($client, 'user'),
            ],
        ]);

        self::assertResponseRedirects('/admin/users/local?id=' . $admin->getId());
        self::assertSame($initialCount, UserFactory::count());
        self::assertSame($initialPassword, $admin->getPassword());
    }

    public function testSuperAdminCanDeleteAdmin(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $adminId = $admin->getId();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/' . $admin->getId() . '/delete';
        $client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($client, 'delete' . $admin->getId()),
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialCount - 1, UserFactory::count());
        UserFactory::assert()->notExists(['id' => $adminId]);
    }

    public function testSuperAdminCannotDeleteAdminWithoutValidCsrfToken(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $adminId = $admin->getId();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/' . $admin->getId() . '/delete';
        $client->request(Request::METHOD_POST, $url, [
            '_token' => 'test',
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialCount, UserFactory::count());
        self::assertNotNull(
            UserFactory::repository()->findOneBy(['id' => $adminId])
        );
    }

    public function testSuperAdminCanBatchDeleteAdmins(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin1 = UserFactory::new()->admin()->create();
        $admin1Id = $admin1->getId();
        $admin2 = UserFactory::new()->admin()->create();
        $admin2Id = $admin2->getId();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/batchDelete';
        $client->request(Request::METHOD_POST, $url, [
            '_csrf_token' => $this->generateCsrfToken($client, 'delete user'),
            'id' => [
                $admin1Id,
                $admin2Id,
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialCount - 2, UserFactory::count());
        UserFactory::assert()->notExists(['id' => $admin1Id]);
        UserFactory::assert()->notExists(['id' => $admin2Id]);
    }

    public function testSuperAdminCannotBatchDeleteAdminsWithoutValidCsrfToken(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin1 = UserFactory::new()->admin()->create();
        $admin1Id = $admin1->getId();
        $admin2 = UserFactory::new()->admin()->create();
        $admin2Id = $admin2->getId();
        $client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/batchDelete';
        $client->request(Request::METHOD_POST, $url, [
            '_csrf_token' => 'test',
            'id' => [
                $admin1Id,
                $admin2Id,
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialCount, UserFactory::count());
        UserFactory::assert()->exists(['id' => $admin1Id]);
        UserFactory::assert()->exists(['id' => $admin2Id]);
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
