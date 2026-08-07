<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GroupControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    public function testUserCannotListGroups(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne(['name' => $group1Name]);
        GroupFactory::createOne(['name' => $group2Name]);
        $user->addGroup($group1);

        $client->request('GET', '/groups/');
        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCanListItsDomainGroups(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->admin()->create([
            'domains' => [$domain],
        ]);
        $client->loginUser($user);
        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne([
            'name' => $group1Name,
            'domain' => $domain,
        ]);
        $user->addGroup($group1);
        GroupFactory::createOne(['name' => $group2Name]);

        $crawler = $client->request('GET', '/groups/');

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('td[data-title="Name"]')
            ->each(fn($node) => trim($node->text()));
        self::assertContains($group1Name, $titles);
        self::assertNotContains($group2Name, $titles);
    }

    public function testSuperAdminCanListAllDomainGroups(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);
        $group1Name = 'group1';
        $group2Name = 'group2';
        $group1 = GroupFactory::createOne([
            'name' => $group1Name,
            'domain' => $domain,
        ]);
        GroupFactory::createOne(['name' => $group2Name]);
        $user->addGroup($group1);

        $crawler = $client->request('GET', '/groups/');

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('td[data-title="Name"]')
            ->each(fn($node) => trim($node->text()));
        self::assertContains($group1Name, $titles);
        self::assertContains($group2Name, $titles);
    }

    public function testSuperAdminCanCreateGroups(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);

        $client->request('POST', '/groups/new', [
            'group' => [
                'name' => 'test',
                'policy' => PolicyFactory::random()->getId(),
                'wbRule' => 'block',
                'priority' => '12',
                'domain' => $domain->getId(),
                '_token' => $this->generateCsrfToken($client, 'groups'),
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
}
