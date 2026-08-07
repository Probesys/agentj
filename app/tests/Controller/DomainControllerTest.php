<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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
        $admin = UserFactory::new()->admin([$domain1, $domain2])->create();
        $client->loginUser($admin);

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
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
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
        self::assertSame(2, DomainFactory::count());
    }

    public function testUserCannotCreateDomain(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->user()->create();
        $client->loginUser($user);

        $payload = $this->createPayload($client);
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCannotCreateDomain(): void
    {
        $client = static::createClient();
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin);

        $payload = $this->createPayload($client);
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanCreateDomain(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);

        $payload = $this->createPayload($client);
        $client->request('POST', '/domain/new', $payload);

        self::assertSame(1, DomainFactory::count());
        $domain = DomainFactory::last();
        self::assertTrue($domain->getActive());
        self::assertEquals($domain->getDomain(), $domain->getDomain());
        self::assertEquals('smtp.test2.fr', $domain->getSrvSmtp());
        self::assertEquals(26, $domain->getSmtpPort());
    }

    public function testSuperAdminCanEditDomain(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();

        $payload = $this->createPayload($client, [
            "active" => false,
            "srvSmtp" => "smtp.other.fr",
            "smtpPort" => "27",
            "level" => "2",
            "authorizedSendersSpamLevel" => "10",
        ]);
        $client->request('POST', '/domain/' . $domain->getId() . '/edit', $payload);

        self::assertSame(1, DomainFactory::count());
        $updatedDomain = DomainFactory::find($domain->getId());
        self::assertFalse($updatedDomain->getActive());
        self::assertEquals($domain->getDomain(), $updatedDomain->getDomain());
        self::assertEquals('smtp.other.fr', $updatedDomain->getSrvSmtp());
        self::assertEquals(27, $updatedDomain->getSmtpPort());
        self::assertEquals(2, $updatedDomain->getLevel());
        self::assertEquals(10, $updatedDomain->getAuthorizedSendersSpamLevel());

        self::assertResponseRedirects('/domain/?id=' . $domain->getId());
    }

    /**
     * @param array<mixed>|null $attributes
     * @return array<string, array<string, string>>
     */
    private function createPayload(KernelBrowser $client, ?array $attributes = null): array
    {
        $token = $this->generateCsrfToken($client, 'domain');

        $payload = [
            'domain' => [
                "active" => $attributes['active'] ?? "true",
                "domain" => $attributes['domain'] ?? "test2.fr",
                "srvSmtp" => $attributes['srvSmtp'] ??  "smtp.test2.fr",
                "smtpPort" => $attributes['smtpPort'] ?? "26",
                "wbRule" => $attributes['wbRule'] ?? "enabled",
                "policy" => $attributes['policy'] ?? (string)PolicyFactory::random()->getId(),
                "level" => $attributes['level'] ?? "0.5",
                "authorizedSendersSpamLevel" => $attributes['authorizedSendersSpamLevel'] ?? "5",
                "mailAuthenticationSender" => $attributes['mailAuthenticationSender'] ?? "",
                "_token" => $token,
            ],
        ];

        if ($attributes !== null && !$attributes['active']) {
            unset($payload['domain']['active']);
        }

        return $payload;
    }
}
