<?php

namespace App\Tests\Controller;

use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\SessionHelper;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @phpstan-type QuotaType 'user'|'group'|'domain'
 */
class QuotaControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private RouterInterface $router;
    private TranslatorInterface $translator;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->router = $this->client->getContainer()->get(RouterInterface::class);
        $this->translator = $this->client->getContainer()->get(TranslatorInterface::class);
    }

    public function testUnconnectedUserCannotGetUserQuotaUsages(): void
    {
        $user = UserFactory::new()->user()->create();

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/login');
    }

    public function testUserCanGetItsQuotaUsages(): void
    {
        $limit = 5;
        $period = 10;
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    'quota_emails' => $limit,
                    'quota_seconds' => $period,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($user);

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        $this->assertQuotas(
            type: 'domain',
            percentage: 0,
            period: $period,
            limit:$limit,
            usage: 0,
            remaining: $limit,
        );
    }

    public function testAdminCanGetQuotaUsagesForUsersOfItsDomains(): void
    {
        $limit = 5;
        $period = 10;
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    'quota_emails' => $limit,
                    'quota_seconds' => $period,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        $this->assertQuotas(
            type: 'domain',
            percentage: 0,
            period: $period,
            limit:$limit,
            usage: 0,
            remaining: $limit,
        );
    }

    public function testAdminCannotGetQuotaUsagesForUsersOutsideOfItsDomains(): void
    {
        $limit = 5;
        $period = 10;
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    'quota_emails' => $limit,
                    'quota_seconds' => $period,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$otherDomain])->create();
        $this->client->loginUser($admin);

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSuperAdminCanGetQuotaUsagesForUser(): void
    {
        $limit = 5;
        $period = 10;
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    'quota_emails' => $limit,
                    'quota_seconds' => $period,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        $this->assertQuotas(
            type: 'domain',
            percentage: 0,
            period: $period,
            limit:$limit,
            usage: 0,
            remaining: $limit,
        );
    }

    #[DataProvider('provideQuotasWithLongDuration')]
    public function testQuotaUsagesForUserDisplayShortenedUnits(string $expected, int $period): void
    {
        $limit = 5;
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    'quota_emails' => $limit,
                    'quota_seconds' => $period,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $url = $this->router->generate('user_quotas', [
            'id' => $user->getId(),
        ]);
        $this->client->request(Request::METHOD_GET, $url);

        self::assertSelectorTextContains('[data-test="period"]', $expected);
    }

    /**
     * @param QuotaType $type
     */
    private function assertQuotas(
        string $type,
        int $percentage,
        int $period,
        int $limit,
        int $usage,
        int $remaining,
    ): void {
        // Title
        $expectedTitle = $this->translator->trans('Message.Dialogs.quotas.title', locale: 'en') . ' - ' .
            $this->translator->trans('Message.Dialogs.quotas.category' . ucfirst($type), locale: 'en');
        self::assertSelectorTextContains('[data-test="title"]', $expectedTitle);
        // Percentage
        self::assertSelectorTextContains('[data-test="percentage"]', ((string)$percentage) . '%');
        // Period
        self::assertSelectorTextContains('[data-test="period"]', ((string)$period) . ' s');
        // Limit
        self::assertSelectorTextContains('[data-test="limit"]', (string)$limit);
        // Usage
        self::assertSelectorTextContains('[data-test="usage"]', (string)$usage);
        // Remaining
        self::assertSelectorTextContains('[data-test="remaining"]', (string)$remaining);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideQuotasWithLongDuration(): iterable
    {
        yield 'seconds' => ['25 s', 25];
        yield 'minute' => ['1 min', 60];
        yield 'minutes' => ['2 min', 120];
        yield 'hour' => ['1 h', 3600];
        yield 'hours' => ['2 h', 7200];
        yield 'day' => ['1 d', 86400];
        yield 'days' => ['1.5 d', 129600];
        yield 'week' => ['1 wk', 604800];
        yield 'weeks' => ['3 wks', 1814400];
        yield 'month' => ['1 mo', 2592000];
        yield 'months' => ['3 mos', 7776000];
        yield 'year' => ['1 yr', 31536000];
        yield 'years' => ['3 yrs', 94608000];
    }
}
