<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LdapConnectorFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LdapConnectorControllerTest extends WebTestCase
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

    public function testAdminCanSyncConnector(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::new()->neverExecuted()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = '/ldap/' . $connector->getId() . '/sync-user';
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'sync' . $connector->getId()),
        ]);

        $redirectUrl = '/domain/' . $domain->getId() . '/edit#auth';
        self::assertResponseRedirects($redirectUrl);
        // Sync job has been executed, and connector has been updated
        $this->refresh($connector);
        self::assertNotNull($connector->getLastExecution()['date']);
    }

    public function testAdminCannotSyncConnectorOutsideItsDomains(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::new()->neverExecuted()->create([
            'domain' => $otherDomain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = '/ldap/' . $connector->getId() . '/sync-user';
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'sync' . $connector->getId()),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        // Sync job has not been executed, and connector has not been updated
        $this->refresh($connector);
        self::assertNull($connector->getLastExecution()['date']);
    }

    public function testAdminCannotSyncConnectorWithoutValidCsrfToken(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::new()->neverExecuted()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = '/ldap/' . $connector->getId() . '/sync-user';
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => 'invalid CSRF token',
        ]);

        $redirectUrl = '/domain/' . $domain->getId() . '/edit#auth';
        self::assertResponseRedirects($redirectUrl);
        // Sync job has not been executed, and connector has not been updated
        $this->refresh($connector);
        self::assertNull($connector->getLastExecution()['date']);
    }
}
