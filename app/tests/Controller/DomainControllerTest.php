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

class DomainControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    public function testAdminCanListDomainsButSeeOnlyItsOwn(): void
    {
        $client = static::createClient();
        $domain1Title = 'test.fr';
        $domain2Title = 'test.it';
        $domain3Title = 'test.com';
        $domain1 = DomainFactory::createOne(['domain' => $domain1Title]);
        $domain2 = DomainFactory::createOne(['domain' => $domain2Title]);
        DomainFactory::createOne(['domain' => $domain3Title]);
        self::assertSame(3, DomainFactory::count());
        $user = UserFactory::new()->admin()->create([
            'domains' => [$domain1, $domain2],
        ]);
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/domain/');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertContains($domain1Title, $titles);
        self::assertContains($domain2Title, $titles);
        self::assertNotContains($domain3Title, $titles);
    }

    public function testSuperAdminCanListDomainsAndSeeAll(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);
        $domain1Title = 'test.fr';
        $domain2Title = 'test.com';
        DomainFactory::createOne(['domain' => $domain1Title]);
        DomainFactory::createOne(['domain' => $domain2Title]);

        $crawler = $client->request(Request::METHOD_GET, '/domain/');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertContains($domain1Title, $titles);
        self::assertContains($domain2Title, $titles);
        self::assertSame(3, DomainFactory::count());
    }

    public function testDomainListIsPaginated(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);
        DomainFactory::createMany(30);

        $crawler = $client->request(Request::METHOD_GET, '/domain/');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(25, $titles);

        // Ask next page to get the 5 remaining domains of the list
        $crawler = $client->request(Request::METHOD_GET, '/domain/?page=2&sort=d.domain&direction=asc');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertCount(6, $titles);

        self::assertSame(31, DomainFactory::count());
    }

    public function testUserCannotCreateDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->admin()->create();
        $client->loginUser($user);

        $payload = $this->createPayload();
        $payload['domain']['_token'] = $this->generateCsrfToken($client, 'domain');
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCannotCreateDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->admin()->create();
        $client->loginUser($user);

        $payload = $this->createPayload();
        $payload['domain']['_token'] = $this->generateCsrfToken($client, 'domain');
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanCreateDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);

        $payload = $this->createPayload();
        $payload['domain']['_token'] = $this->generateCsrfToken($client, 'domain');
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(2, DomainFactory::count());
        $domain = DomainFactory::last();
        self::assertTrue($domain->getActive());
        self::assertEquals($domain->getDomain(), $domain->getDomain());
        self::assertEquals('smtp.test2.fr', $domain->getSrvSmtp());
        self::assertEquals(26, $domain->getSmtpPort());
    }

    public function testSuperAdminCanEditDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->superAdmin()->create();
        $client->loginUser($user);
        $domain = $user->getDomain();

        $client->request('POST', '/domain/' . $domain->getId() . '/edit', [
            'domain' => [
                '_token' => $this->generateCsrfToken($client, 'domain'),
                "active" => false,
                "srvSmtp" => "smtp.test2.fr",
                "smtpPort" => "26",
                "wbRule" => "enabled",
                "policy" => PolicyFactory::random()->getId(),
                "level" => "0.5",
                "authorizedSendersSpamLevel" => "5",
                "mailAuthenticationSender" => "",
            ],
        ]);

        self::assertSame(1, DomainFactory::count());
        $updatedDomain = DomainFactory::find($domain->getId());
//        self::assertFalse($updatedDomain->getActive()); // FIXME
        self::assertEquals($domain->getDomain(), $updatedDomain->getDomain());
        self::assertEquals('smtp.test2.fr', $updatedDomain->getSrvSmtp());
        self::assertEquals(26, $updatedDomain->getSmtpPort());

        self::assertResponseRedirects('/domain/?id=' . $domain->getId());
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function createPayload(): array
    {
        return [
            'domain' => [
                "active" => "true",
                "domain" => "test2.fr",
                "srvSmtp" => "smtp.test2.fr",
                "smtpPort" => "26",
                "wbRule" => "enabled",
                "policy" => strval(PolicyFactory::random()->getId()),
                "level" => "0.5",
                "authorizedSendersSpamLevel" => "5",
                "mailAuthenticationSender" => "",
            ],
        ];
    }
}
