<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DashboardControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private RouterInterface $router;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->router = $this->client->getContainer()->get(RouterInterface::class);
        $this->translator = $this->client->getContainer()->get(TranslatorInterface::class);
    }

    public function testUserShouldSeeQuotasWidget(): void
    {
        $domain = DomainFactory::createOne();
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);

        $url = $this->router->generate('dashboard_quotas');
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorExists(
            '[data-test="quotas-content"]',
            $this->translator->trans('message.dialogs.quotas.noQuota'),
        );
    }

    public function testAdminShouldNotSeeQuotasWidget(): void
    {
        $domain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = $this->router->generate('dashboard_quotas');
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorExists(
            '[data-test="quotas-content"]',
            $this->translator->trans('message.dialogs.quotas.noquota'),
        );
    }

    public function testSuperAdminShouldNotSeeQuotasWidget(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $url = $this->router->generate('dashboard_quotas');
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorExists(
            '[data-test="quotas-content"]',
            $this->translator->trans('Message.Dialogs.quotas.noQuota'),
        );
    }
}
