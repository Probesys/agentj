<?php

namespace App\Tests\Controller;

use App\Entity\Domain;
use App\Entity\SenderRule;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testUserCannotListUsers(): void
    {
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/users/email');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCannotListUsersIfNoDomain(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/users/email');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListUsersIfNoDomain(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $this->client->request('GET', '/admin/users/email');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanListUsersOfItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $user1 = UserFactory::new()->user($domain)->create();
        $user2 = UserFactory::new()->user($domain)->create();
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user($otherDomain)->create();

        $crawler = $this->client->request('GET', '/admin/users/email');

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
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domainUser1 = UserFactory::new()->user($domain)->create();
        $domainUser2 = UserFactory::new()->user($domain)->create();
        $otherDomain = DomainFactory::createOne();
        $otherDomainUser = UserFactory::new()->user($otherDomain)->create();

        $crawler = $this->client->request('GET', '/admin/users/email');

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
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@test.fr',
                'imapLogin' => 'testUser',
                'report' => true,
            ],
        ]);

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanCreateUsersInItsDomains(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
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
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        self::assertEquals(2, UserFactory::count()); // 1 admin user, and 1 user for domain rule

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => 'invalidCsrfToken',
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode()); // API always returns 200
        $response = $this->client->getResponse()->getContent();
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
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $otherDomain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode()); // API always returns 200
        self::assertSame($initialCount, UserFactory::count()); // But no user created
    }

    public function testSuperAdminCanCreateUsersInAllDomains(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@' . $domain->getDomain(),
                'imapLogin' => '',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount + 1, UserFactory::count());
        $createdUser = UserFactory::findBy(['email' => 'test@' . $domain->getDomain()])[0];
        self::assertSame('Test User', $createdUser->getFullname());
        self::assertNull($createdUser->getImapLogin());
        self::assertTrue($createdUser->getReport());
    }

    public function testSuperAdminCannotCreateUsersWithEmptyForm(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", []);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCannotCreateUserForANonExistingDomain(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'test@nonExistingDomain.tld',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateUserWithAnAlreadyUsedEmail(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => $user->getEmail(),
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateUserThatIsPresentInImap(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
            'imapLogin' => 'test',
        ]);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, "/admin/users/email/newUser", [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'Test User',
                'email' => 'other@' . $domain->getDomain(),
                'imapLogin' => 'test',
                'report' => true,
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message": "This IMAP login already exists. You can\'t use it"}',
            $content,
        );
    }

    public function testAdminCanEditUsersInItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $group = GroupFactory::new()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@' . $domain->getDomain();
        self::assertCount(0, $user->getGroups());
        $this->client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
                'groups' => [
                    $group->getId(),
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        $this->refresh($user);
        self::assertSame($newFullname, $user->getFullname());
        self::assertSame($newEmail, $user->getEmail());
        self::assertCount(1, $user->getGroups());
    }

    public function testSuperAdminCanEditUsers(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@' . $domain->getDomain();
        $this->client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        $this->refresh($user);
        self::assertSame($newFullname, $user->getFullname());
        self::assertSame($newEmail, $user->getEmail());
    }

    public function testSuperAdminCannotEditUsersWithANotExistingDomain(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = 'other@not-existing.tld';
        $this->client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
        $this->refresh($user);
        self::assertNotSame($newFullname, $user->getFullname());
        self::assertNotSame($newEmail, $user->getEmail());
    }

    public function testSuperAdminCannotEditUsersWithAnAlreadyUsedMail(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $otherUser = UserFactory::new()->user($domain)->create([
            'email' => 'otherTest@' . $domain->getDomain(),
        ]);
        $initialCount = UserFactory::count();

        $newFullname = 'Other name';
        $newEmail = $otherUser->getEmail();
        $this->client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
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
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
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
        $this->client->request(Request::METHOD_POST, '/admin/users/email/' . $user->getId() . '/edit', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $newFullname,
                'email' => $newEmail,
                'imapLogin' => 'test',
            ],
        ]);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This IMAP login already exists. You can\'t use it"}',
            $content,
        );
        $this->refresh($user);
        self::assertNotSame($newFullname, $user->getFullname());
        self::assertNotSame($newEmail, $user->getEmail());
    }

    public function testSuperAdminCanListAllAliases(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $alias = UserFactory::new()->alias($user)->create();

        $crawler = $this->client->request('GET', '/admin/users/alias');

        self::assertResponseIsSuccessful();
        $seenAliases = $crawler
            ->filter('td[data-title="Alias"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(1, $seenAliases);
        self::assertContains($alias->getEmail(), $seenAliases);
    }

    public function testSuperAdminCanCreateAnAlias(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $email = 'test@' . $domain->getDomain();
        $user = UserFactory::new()->user($domain)->create([
            'email' => $email,
        ]);
        $initialCount = UserFactory::count();

        $fullname = 'test fullname';
        $username = $user->getEmail();
        $alias = 'other@' . $domain->getDomain();
        $this->client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
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

    public function testCreateAnAliasForAUserWithSenderRulesShouldApplyThoseRules(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
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
        $this->client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $sender->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
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

    public function testSuperAdminCannotCreateAnAliasForNotExistingDomain(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $email = 'test@' . $domain->getDomain();
        $user = UserFactory::new()->user($domain)->create([
            'email' => $email,
        ]);
        $initialCount = UserFactory::count();

        $fullname = 'test fullname';
        $username = $user->getEmail();
        $alias = 'other@other-domain.tld';
        $this->client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => $fullname,
                'username' => $username,
                'email' => $alias,
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"The domain does not exist"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCannotCreateAlreadyExistingAlias(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $user = UserFactory::new()->user($domain)->create([
            'email' => 'test@' . $domain->getDomain(),
        ]);
        $aliasEmail = 'alias@' . $domain->getDomain();
        $alias = UserFactory::new()->user($domain)->create([
            'email' => $aliasEmail,
            'originalUser' => $user,
        ]);
        $initialCount = UserFactory::count();

        $this->client->request(Request::METHOD_POST, '/admin/users/newAlias', [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $alias->getEmail(),
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This alias is already used. You can\'t use it"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCanEditAlias(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
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
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $newEmail,
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"success","message":"The update has been successfully completed"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
        $this->refresh($alias);
        self::assertSame($newEmail, $alias->getEmail());
        self::assertSame($newEmail, $alias->getUsername());
    }

    public function testSuperAdminCannotEditAlreadyExistingAlias(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
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
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                '_token' => $this->generateCsrfToken($this->client, 'user'),
                'fullname' => 'test',
                'username' => $user->getEmail(),
                'email' => $otherAlias->getEmail(),
                'originalUser' => $user->getId(),
                'report' => 1,
            ],
        ]);

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
        self::assertSame($initialCount, UserFactory::count());
        $this->refresh($alias);
        self::assertNotSame($user->getEmail(), $alias->getUsername());
    }

    public function testUserCannotListAdmins(): void
    {
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/admin/users/local');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListAllAdmins(): void
    {
        $domain1 = DomainFactory::createOne();
        $admin1 = UserFactory::new()->admin([$domain1])->create();
        $domain2 = DomainFactory::createOne();
        $admin2 = UserFactory::new()->admin([$domain2])->create();
        $domain3 = DomainFactory::createOne();
        $admin3 = UserFactory::new()->admin([$domain3])->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $crawler = $this->client->request(Request::METHOD_GET, '/admin/users/local');

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
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_POST, '/admin/users/local/new');

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanCreateAdmins(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($this->client, $domain);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertSame($initialCount, UserFactory::count());
    }

    public function testSuperAdminCanCreateAdmins(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($this->client, $domain);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount + 1, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
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
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $domains = array_map(fn($domain) => $domain->getId(), DomainFactory::all());
        $payload = $this->createPayload($this->client, $domain, [
            'domains' => $domains,
            'roles' => '["ROLE_SUPER_ADMIN"]',
        ]);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount + 1, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
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
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        UserFactory::new()->user()->create(['username' => 'test']);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($this->client, $domain);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This login already exists. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateAdminWithAlreadyUsedEmail(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $email = 'test@test.fr';
        UserFactory::new()->user()->create(['email' => $email]);
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($this->client, $domain, [
            'email' => $email,
        ]);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"This email is already used. You can\'t use it"}',
            $content,
        );
    }

    public function testSuperAdminCannotCreateAdminWithNotIdenticalPasswords(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $payload = $this->createPayload($this->client, $domain, [
            'password' => [
                'second' => 'other',
            ],
        ]);
        $this->client->request(Request::METHOD_POST, '/admin/users/local/new', $payload);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{"status":"danger","message":"Password confirmation is not valid"}',
            $content,
        );
    }

    public function testSuperAdminCanEditAdminsToSuperAdmins(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $newFullname = 'other';
        $newUsername = 'other';
        $newEmail = 'other@' . $domain->getDomain();
        $url = '/admin/users/local/' . $admin->getId() . '/edit';
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                'email' => $newEmail,
                'fullname' => $newFullname,
                'username' => $newUsername,
                'roles' => '["ROLE_SUPER_ADMIN"]',
                'domains' => [],
                '_token' => $this->generateCsrfToken($this->client, 'user'),
            ]
        ]);

        self::assertResponseIsSuccessful();
        $this->refresh($admin);
        self::assertSame($initialCount, UserFactory::count());
        self::assertSame($newFullname, $admin->getFullname());
        self::assertSame($newUsername, $admin->getUsername());
        self::assertSame($newEmail, $admin->getEmail());
        self::assertSame(["ROLE_SUPER_ADMIN"], $admin->getRoles());
    }

    public function testSuperAdminCannotEditAdminsIfUsernameAlreadyUsed(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        UserFactory::new()->user()->create(['username' => 'other']);
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $newFullname = 'other';
        $newUsername = 'other';
        $newEmail = 'other@' . $domain->getDomain();
        $url = '/admin/users/local/' . $admin->getId() . '/edit';
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                'email' => $newEmail,
                'fullname' => $newFullname,
                'username' => $newUsername,
                'roles' => '["ROLE_SUPER_ADMIN"]',
                'domains' => [],
                '_token' => $this->generateCsrfToken($this->client, 'user'),
            ]
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame($initialCount, UserFactory::count());
        $content = $this->client->getResponse()->getContent();
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
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $initialPassword = $admin->getPassword();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/local/' . $admin->getId() . '/changePassword';
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                'password' => [
                    'first' => 'secret',
                    'second' => 'secret',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'user'),
            ],
        ]);

        self::assertResponseRedirects('/admin/users/local?id=' . $admin->getId());
        self::assertSame($initialCount, UserFactory::count());
        $this->refresh($admin);
        self::assertNotEquals($initialPassword, $admin->getPassword());
    }

    public function testSuperAdminCannotChangeAdminPasswordIfPasswordsDoNotMatch(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $initialPassword = $admin->getPassword();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/local/' . $admin->getId() . '/changePassword';
        $this->client->request(Request::METHOD_POST, $url, [
            'user' => [
                'password' => [
                    'first' => 'secret',
                    'second' => 'other',
                ],
                '_token' => $this->generateCsrfToken($this->client, 'user'),
            ],
        ]);

        self::assertResponseRedirects('/admin/users/local?id=' . $admin->getId());
        self::assertSame($initialCount, UserFactory::count());
        $this->refresh($admin);
        self::assertSame($initialPassword, $admin->getPassword());
    }

    public function testSuperAdminCanDeleteAdmin(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $adminId = $admin->getId();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/' . $admin->getId() . '/delete';
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'delete' . $admin->getId()),
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialCount - 1, UserFactory::count());
        UserFactory::assert()->notExists(['id' => $adminId]);
    }

    public function testSuperAdminCannotDeleteAdminWithoutValidCsrfToken(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin = UserFactory::new()->admin()->create();
        $adminId = $admin->getId();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/' . $admin->getId() . '/delete';
        $this->client->request(Request::METHOD_POST, $url, [
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
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin1 = UserFactory::new()->admin()->create();
        $admin1Id = $admin1->getId();
        $admin2 = UserFactory::new()->admin()->create();
        $admin2Id = $admin2->getId();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/batchDelete';
        $this->client->request(Request::METHOD_POST, $url, [
            '_csrf_token' => $this->generateCsrfToken($this->client, 'delete user'),
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
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $admin1 = UserFactory::new()->admin()->create();
        $admin1Id = $admin1->getId();
        $admin2 = UserFactory::new()->admin()->create();
        $admin2Id = $admin2->getId();
        $this->client->loginUser($superAdmin);
        $initialCount = UserFactory::count();

        $url = '/admin/users/email/batchDelete';
        $this->client->request(Request::METHOD_POST, $url, [
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
