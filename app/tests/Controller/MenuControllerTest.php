<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MenuControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAdminSeesImpersonatedUsersSharedMailboxesFromManagedDomains(): void
    {
        $client = static::createClient();
        $managedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        $userB = UserFactory::new()->user($managedDomain)->create();
        $userC = UserFactory::new()->user($managedDomain)->create(['sharedWith' => [$userB]]);
        $otherDomainUser = UserFactory::new()->user(DomainFactory::createOne())->create(['sharedWith' => [$userB]]);
        $client->loginUser($admin);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userB->getUsername()]);
        self::assertResponseRedirects('/', 302);

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        $menuItems = $this->getUserMenuItems($crawler);

        self::assertContains($userC->getUsername(), $menuItems);
        self::assertNotContains($userB->getUsername(), $menuItems);
        self::assertNotContains($otherDomainUser->getUsername(), $menuItems);
    }

    public function testUserSeesOtherSharedMailboxesWhenImpersonating(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $userA = UserFactory::new()->user($domain)->create();
        $userB = UserFactory::new()->user($domain)->create(['sharedWith' => [$userA]]);
        $userC = UserFactory::new()->user($domain)->create(['sharedWith' => [$userA]]);
        $client->loginUser($userA);

        $client->request(Request::METHOD_GET, '/', ['_switch_user' => $userB->getUsername()]);
        self::assertResponseRedirects('/', 302);

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        $menuItems = $this->getUserMenuItems($crawler);

        self::assertContains($userC->getUsername(), $menuItems);
        self::assertNotContains($userB->getUsername(), $menuItems);
    }

    /**
     * @return string[]
     */
    private function getUserMenuItems(Crawler $crawler): array
    {
        return $crawler
            ->filter('#userDropdown + .dropdown-menu a.dropdown-item')
            ->each(fn (Crawler $node) => trim($node->text()));
    }
}
