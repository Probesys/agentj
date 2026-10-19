<?php

namespace App\Tests\Service;

use App\Service\RateLimitService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\MessageHelper;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RateLimitServiceTest extends WebTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    private RateLimitService $rateLimitService;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        // $this->client = static::getClient();
        $this->rateLimitService = static::getContainer()->get(RateLimitService::class);
    }

    /**
     * @param list<array<string, int>> $expected
     * @param list<array<string, int>> $quotas
     */
    #[DataProvider('provideQuotas')]
    public function testGetApplicableQuotasRespectsPriorityUser(array $expected, array $quotas): void
    {
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    "quota_emails" => 100,
                    "quota_seconds" => 60,
                ],
            ],
        ]);
        $group = GroupFactory::createOne([
            'domain' => $domain,
            'quota' => [
                [
                    "quota_emails" => 20,
                    "quota_seconds" => 20,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create([
            'quota' => $quotas,
        ]);
        $user->addGroup($group);

        $quotas = $this->rateLimitService->getApplicableQuotas($user);

        self::assertSame($expected, $quotas);
    }

    /**
     * @param list<array<string, int>> $expected
     * @param list<array<string, int>> $quotas
     */
    #[DataProvider('provideQuotas')]
    public function testGetApplicableQuotasRespectsPriorityGroup(array $expected, array $quotas): void
    {
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    "quota_emails" => 100,
                    "quota_seconds" => 60,
                ],
            ],
        ]);
        $group = GroupFactory::createOne([
            'domain' => $domain,
            'quota' => $quotas,
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $user->addGroup($group);

        $quota = $this->rateLimitService->getApplicableQuotas($user);

        self::assertSame($expected, $quota);
    }

    public function testGetApplicableQuotasRespectsPriorityGroupMultiple(): void
    {
        $quota1 = [
            'quota_emails' => 10,
            'quota_seconds' => 60,
        ];
        $quota2 = [
            'quota_emails' => 20,
            'quota_seconds' => 10,
        ];
        $quota3 = [
            'quota_emails' => 10,
            'quota_seconds' => 20,
        ];
        $domain = DomainFactory::createOne([
            'quota' => [
                [
                    "quota_emails" => 100,
                    "quota_seconds" => 5,
                ],
            ],
        ]);
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'quota' => [
                $quota1,
                $quota2,
            ],
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'quota' => [
                $quota3,
                [
                    'quota_emails' => 20,
                    'quota_seconds' => 40,
                ],
            ],
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $user->addGroup($group1);
        $user->addGroup($group2);

        $quotas = $this->rateLimitService->getApplicableQuotas($user);

        $expected = [
            $quota1,
            $quota2,
            $quota3,
        ];
        self::assertSame($expected, $quotas);
    }

    /**
     * @param list<array<string, int>> $expected
     * @param list<array<string, int>> $quotas
     */
    #[DataProvider('provideQuotas')]
    public function testGetApplicableQuotasRespectsPriorityDomain(array $expected, array $quotas): void
    {
        $domain = DomainFactory::createOne([
            'quota' => $quotas,
        ]);
        $group = GroupFactory::createOne([
            'domain' => $domain,
        ]);
        $user = UserFactory::new()->user($domain)->create();
        $user->addGroup($group);

        $quota = $this->rateLimitService->getApplicableQuotas($user);

        self::assertSame($expected, $quota);
    }

    public function testAdminAlsoHaveQuotasAssociatedToItsDomain(): void
    {
        $expected = [
            [
                'quota_emails' => 100,
                'quota_seconds' => 10,
            ],
        ];
        $domain = DomainFactory::createOne([
            'quota' => $expected,
        ]);
        $otherDomain1 = DomainFactory::createOne([
            'quota' => [
                [
                    "quota_emails" => 100,
                    "quota_seconds" => 50,
                ],
            ],
        ]);
        $otherDomain2 = DomainFactory::createOne([
            'quota' => [
                [
                    "quota_emails" => 100,
                    "quota_seconds" => 100,
                ],
            ],
        ]);
        $admin = UserFactory::new()->admin([$otherDomain1, $otherDomain2])->create([
            'domain' => $domain
        ]);

        $quotas = $this->rateLimitService->getApplicableQuotas($admin);

        self::assertSame($expected, $quotas);
    }

    public function testSuperAdminHasNoEmail(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();

        $quotas = $this->rateLimitService->getApplicableQuotas($superAdmin);

        self::assertNull($quotas);
    }

    /**
     * @return iterable<string, array{
     *     list<array<string, int>>,
     *     list<array<string, int>>,
     * }>
     */
    public static function provideQuotas(): iterable
    {
        yield 'simple' => [
            // Expected
            [
                [
                    'quota_emails' => 5,
                    'quota_seconds' => 10,
                ],
            ],
            // Inserted
            [
                [
                    'quota_emails' => 5,
                    'quota_seconds' => 10,
                ],
            ],
        ];
        yield 'multiple' => [
            // Expected
            [
                [
                    'quota_emails' => 10,
                    'quota_seconds' => 60,
                ],
                [
                    'quota_emails' => 20,
                    'quota_seconds' => 10,
                ],
                [
                    'quota_emails' => 50,
                    'quota_seconds' => 120,
                ],
            ],
            // Inserted
            [
                [
                    'quota_emails' => 10,
                    'quota_seconds' => 60,
                ],
                [
                    'quota_emails' => 20,
                    'quota_seconds' => 10,
                ],
                [
                    'quota_emails' => 50,
                    'quota_seconds' => 120,
                ],
                [
                    'quota_emails' => 100,
                    'quota_seconds' => 300,
                ],
            ],
        ];
    }

    public function testUserQuotaUsageIsComputedFull(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create([
            'quota' => [
                [
                    'quota_emails' => 3,
                    'quota_seconds' => 10,
                ],
                [
                    'quota_emails' => 2,
                    'quota_seconds' => 5,
                ],
            ]
        ]);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $elevenSecondsAgo = new DateTimeImmutable('-11 seconds');
        $sixSecondsAgo = new DateTimeImmutable('-6 seconds');
        $twoSecondsAgo = new DateTimeImmutable('-2 seconds');
        // 1 out message outside quota periods
        $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
            'timeIso' => $elevenSecondsAgo->format('Ymd\THis\Z'),
            'timeNum' => $elevenSecondsAgo->getTimestamp(),
        ]);
        // 1 out message inside first quota periods
        $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
            'timeIso' => $sixSecondsAgo->format('Ymd\THis\Z'),
            'timeNum' => $sixSecondsAgo->getTimestamp(),
        ]);
        // 2 out messages inside first and second quota periods
        for ($i = 0; $i < 2; $i++) {
            $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
                'timeIso' => $twoSecondsAgo->format('Ymd\THis\Z'),
                'timeNum' => $twoSecondsAgo->getTimestamp(),
            ]);
        }

        $quotaUsages = $this->rateLimitService->getQuotaUsage($sender, $addrS);

        $expected = [
            [
                'limit' => 3,
                'period' => 10,
                'current' => 3,
                'remaining' => 0,
            ],
            [
                'limit' => 2,
                'period' => 5,
                'current' => 2,
                'remaining' => 0,
            ],
        ];
        self::assertSame($expected, $quotaUsages);
    }

    public function testUserQuotaUsageIsComputedPartial(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create([
            'quota' => [
                [
                    'quota_emails' => 3,
                    'quota_seconds' => 10,
                ],
                [
                    'quota_emails' => 2,
                    'quota_seconds' => 5,
                ],
            ]
        ]);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $elevenSecondsAgo = new DateTimeImmutable('-11 seconds');
        $sixSecondsAgo = new DateTimeImmutable('-6 seconds');
        $twoSecondsAgo = new DateTimeImmutable('-2 seconds');
        // 1 out message outside quota periods
        $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
            'timeIso' => $elevenSecondsAgo->format('Ymd\THis\Z'),
            'timeNum' => $elevenSecondsAgo->getTimestamp(),
        ]);
        // 1 out message inside first quota periods
        $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
            'timeIso' => $sixSecondsAgo->format('Ymd\THis\Z'),
            'timeNum' => $sixSecondsAgo->getTimestamp(),
        ]);
        // 1 out message inside first and second quota periods
        $this->setupMail($addrS, [$addrR], direction: 'outgoing', messageAttributes: [
            'timeIso' => $twoSecondsAgo->format('Ymd\THis\Z'),
            'timeNum' => $twoSecondsAgo->getTimestamp(),
        ]);

        $quotaUsages = $this->rateLimitService->getQuotaUsage($sender, $addrS);

        $expected = [
            [
                'limit' => 3,
                'period' => 10,
                'current' => 2,
                'remaining' => 1,
            ],
            [
                'limit' => 2,
                'period' => 5,
                'current' => 1,
                'remaining' => 1,
            ],
        ];
        self::assertSame($expected, $quotaUsages);
    }
}
