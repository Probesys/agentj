<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GroupControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testUserCannotListGroups(): void
    {
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne(['name' => $group1Name]);
        GroupFactory::createOne(['name' => $group2Name]);
        $user->addGroup($group1);

        $this->client->request(Request::METHOD_GET, '/groups/');
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanListItsDomainGroups(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne([
            'name' => $group1Name,
            'domain' => $domain,
        ]);
        $admin->addGroup($group1);
        GroupFactory::createOne(['name' => $group2Name]);

        $crawler = $this->client->request(Request::METHOD_GET, '/groups/');

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('td[data-title="Name"]')
            ->each(fn($node) => trim($node->text()));
        self::assertContains($group1Name, $titles);
        self::assertNotContains($group2Name, $titles);
    }

    public function testSuperAdminCanListAllDomainGroups(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne([
            'name' => $group1Name,
            'domain' => $domain,
        ]);
        GroupFactory::createOne(['name' => $group2Name]);
        $superAdmin->addGroup($group1);

        $crawler = $this->client->request(Request::METHOD_GET, '/groups/');

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('td[data-title="Name"]')
            ->each(fn($node) => trim($node->text()));
        self::assertContains($group1Name, $titles);
        self::assertContains($group2Name, $titles);
    }

    public function testUserCannotCreateGroups(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_POST, '/groups/new', [
            'group' => [
                'name' => 'test',
                'policy' => PolicyFactory::random()->getId(),
                'wbRule' => 'block',
                'priority' => '12',
                'domain' => $domain->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'groups'),
            ],
        ]);

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCannotCreateGroupsOutsideItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_POST, '/groups/new', [
            'group' => [
                'name' => 'test',
                'policy' => PolicyFactory::random()->getId(),
                'wbRule' => 'block',
                'priority' => '12',
                'domain' => $otherDomain->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'groups'),
            ],
        ]);

        self::assertResponseIsSuccessful(); // Response code is 200 even if creation refused
        self::assertEquals(0, GroupFactory::count());
    }

    public function testSuperAdminCanCreateGroups(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_POST, '/groups/new', [
            'group' => [
                'name' => 'test',
                'policy' => PolicyFactory::random()->getId(),
                'wbRule' => 'block',
                'priority' => '12',
                'domain' => $domain->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'groups'),
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertEquals(1, GroupFactory::count());
        $createdGroup = GroupFactory::repository()->first();
        self::assertEquals('test', $createdGroup->getName());
        self::assertEquals('block', $createdGroup->getWbRule());
        self::assertEquals(12, $createdGroup->getPriority());
        self::assertEquals($domain, $createdGroup->getDomain());
    }

    public function testSuperAdminCannotCreateGroupsWithInvalidCsrf(): void
    {
        $domain = DomainFactory::createOne();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_POST, '/groups/new', [
            'group' => [
                'name' => 'test',
                'policy' => PolicyFactory::random()->getId(),
                'wbRule' => 'block',
                'priority' => '12',
                'domain' => $domain->getId(),
                '_token' => 'wrong CSRF domain',
            ],
        ]);

        self::assertResponseIsSuccessful(); // Response code is 200 even if creation refused
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('The CSRF token is invalid. Please try to resubmit the form.', $content);
        self::assertEquals(0, GroupFactory::count());
    }

    public function testAdminCanBatchDeleteGroupFromItsDomains(): void
    {
        $domain1 = DomainFactory::createOne();
        $domain2 = DomainFactory::createOne();
        $domain3 = DomainFactory::createOne();
        $group1 = GroupFactory::createOne([
            'domain' => $domain1,
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain2,
        ]);
        $group3 = GroupFactory::createOne([
            'domain' => $domain3,
        ]);
        $admin = UserFactory::new()->admin([$domain1, $domain2])->create();
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_POST, '/groups/batchDelete', [
            'id' => [
                $group1->getId(),
                $group2->getId(),
                $group3->getId(),
            ],
            '_csrf_token' => $this->generateCsrfToken($this->client, 'delete group'),
        ]);

        self::assertResponseRedirects('/');
        GroupFactory::assert()->notExists([
            'id' => $group1->getId(),
        ]);
        GroupFactory::assert()->notExists([
            'id' => $group2->getId(),
        ]);
        GroupFactory::assert()->exists([
            'id' => $group3->getId(),
        ]);
    }

    public function testAdminCannotBatchDeleteGroupsWithoutValidCsrf(): void
    {
        $domain1 = DomainFactory::createOne();
        $domain2 = DomainFactory::createOne();
        $domain3 = DomainFactory::createOne();
        $group1 = GroupFactory::createOne([
            'domain' => $domain1,
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain2,
        ]);
        $group3 = GroupFactory::createOne([
            'domain' => $domain3,
        ]);
        $admin = UserFactory::new()->admin([$domain1, $domain2])->create();
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_POST, '/groups/batchDelete', [
            'id' => [
                $group1->getId(),
                $group2->getId(),
                $group3->getId(),
            ],
            '_csrf_token' => 'invalid CSRF token',
        ]);

        self::assertResponseRedirects('/');
        GroupFactory::assert()->exists([
            'id' => $group1->getId(),
        ]);
        GroupFactory::assert()->exists([
            'id' => $group2->getId(),
        ]);
        GroupFactory::assert()->exists([
            'id' => $group3->getId(),
        ]);
    }
}
