<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LdapConnectorFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ConnectorControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testUserCannotDeleteConnectorInItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::createOne([
            'domain' => $domain,
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);
        $initialConnectorCount = LdapConnectorFactory::count();

        $url = '/connector/delete/' . $connector->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'delete' . $connector->getId()),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame($initialConnectorCount, LdapConnectorFactory::count());
    }

    public function testAdminCanDeleteConnector(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::createOne([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $initialConnectorCount = LdapConnectorFactory::count();

        $url = '/connector/delete/' . $connector->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'delete' . $connector->getId()),
        ]);

        $redirectUrl = '/domain/' . $domain->getId() . '/edit#auth';
        self::assertResponseRedirects($redirectUrl);
        self::assertSame($initialConnectorCount - 1, LdapConnectorFactory::count());
    }

    public function testAdminCannotDeleteConnectorWithoutValidCsrfToken(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::createOne([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $initialConnectorCount = LdapConnectorFactory::count();

        $url = '/connector/delete/' . $connector->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => 'invalid CSRF token',
        ]);

        $redirectUrl = '/domain/' . $domain->getId() . '/edit#auth';
        self::assertResponseRedirects($redirectUrl);
        self::assertSame($initialConnectorCount, LdapConnectorFactory::count());
    }

    public function testAdminCannotDeleteConnectorOutsideItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        $initialConnectorCount = LdapConnectorFactory::count();

        $url = '/connector/delete/' . $connector->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'delete' . $connector->getId()),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame($initialConnectorCount, LdapConnectorFactory::count());
    }

    public function testSuperAdminCanDeleteConnector(): void
    {
        $domain = DomainFactory::createOne();
        $connector = LdapConnectorFactory::createOne([
            'domain' => $domain,
        ]);
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $initialConnectorCount = LdapConnectorFactory::count();

        $url = '/connector/delete/' . $connector->getId();
        $this->client->request(Request::METHOD_POST, $url, [
            '_token' => $this->generateCsrfToken($this->client, 'delete' . $connector->getId()),
        ]);

        $redirectUrl = '/domain/' . $domain->getId() . '/edit#auth';
        self::assertResponseRedirects($redirectUrl);
        self::assertSame($initialConnectorCount - 1, LdapConnectorFactory::count());
    }
}
