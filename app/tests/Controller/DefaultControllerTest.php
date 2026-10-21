<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DefaultControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->router = $this->client->getContainer()->get(RouterInterface::class);
    }

    // FIXME
    // public function testUserShouldSeeQuotasWidget(): void
    // {
    //     $domain = DomainFactory::createOne();
    //     $user = UserFactory::new()->user($domain)->create();
    //     $this->client->loginUser($user);

    //     $url = $this->router->generate('homepage');
    //     $this->client->request(Request::METHOD_GET, $url);

    //     self::assertSelectorExists('[data-test="quotas-content"]');
    // }

    public function testAdminShouldNotSeeQuotasWidget(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = $this->router->generate('homepage');
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorNotExists('[data-test="quotas-content"]');
    }

    public function testSuperAdminShouldNotSeeQuotasWidget(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $url = $this->router->generate('homepage');
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorNotExists('[data-test="quotas-content"]');
    }
}
