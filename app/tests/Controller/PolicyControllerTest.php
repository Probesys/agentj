<?php

namespace App\Tests\Controller;

use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class PolicyControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;
    use SessionHelper;

    public function testUserCannotListPolicies(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $client->loginUser($user);

        PolicyFactory::createOne();

        $client->request('GET', '/policy/');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCannotListPolicies(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => '["ROLE_ADMIN"]']);
        $client->loginUser($user);

        PolicyFactory::createOne();

        $client->request('GET', '/policy/');

        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testSuperAdminCanListPolicies(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => '["ROLE_SUPER_ADMIN"]']);
        $client->loginUser($user);

        $policy = PolicyFactory::createOne();

        $crawler = $client->request('GET', '/policy/');
        self::assertResponseIsSuccessful();

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn($node) => trim($node->text()));

        self::assertContains($policy->getPolicyName(), $titles);
    }

    public function testSuperAdminCanCreatePolicy(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => '["ROLE_SUPER_ADMIN"]']);
        $client->loginUser($user);
        $initialCount = PolicyFactory::count(); // UserFactory also create a Policy

        $policyName = 'testPolicy';
        $client->request('POST', '/policy/new', [
            'policy' => [
                'policyName' => $policyName,
                '_token' => $this->generateCsrfToken($client, 'policy'),
            ]
        ]);

        self::assertSame($initialCount + 1, PolicyFactory::count());
        self::assertResponseRedirects('/policy/');
    }

    public function testSuperAdminCanEditPolicy(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => '["ROLE_SUPER_ADMIN"]']);
        $client->loginUser($user);
        $policyName = 'testPolicy';
        $policy = PolicyFactory::createOne(['policyName' => $policyName]);
        $initialCount = PolicyFactory::count();

        $newPolicyName = 'testNewPolicy';
        $payload = $this->getPayload($newPolicyName);
        $payload['policy']['_token'] = $this->generateCsrfToken($client, 'policy');
        $client->request('POST', '/policy/' . $policy->getId() . '/edit', $payload);

        self::assertSame($initialCount, PolicyFactory::count());
        $updatedPolicy = PolicyFactory::find($policy->getId());
        self::assertSame($newPolicyName, $updatedPolicy->getPolicyName());
        self::assertResponseRedirects('/policy/?id=' . $policy->getId());
    }

    public function testSuperAdminCanDeletePolicy(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['roles' => '["ROLE_SUPER_ADMIN"]']);
        $client->loginUser($user);
        $policyName = 'testPolicy';
        $policy = PolicyFactory::createOne(['policyName' => $policyName]);
        $initialCount = PolicyFactory::count();

        $client->request('POST', '/policy/' . $policy->getId() . '/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete'),
        ]);

        self::assertSame($initialCount - 1, PolicyFactory::count());
        self::assertResponseRedirects('/policy/');
    }

    /**
     * @return array<array<string,string|null>>
     */
    public function getPayload(string $name): array
    {
        return [
            "policy" => [
                "policyName" => $name,
                "virusLover" => "N",
                "spamLover" => "N",
                "bannedFilesLover" => "N",
                "badHeaderLover" => "N",
                "uncheckedLover" => null,
                "bypassVirusChecks" => "N",
                "bypassSpamChecks" => "N",
                "bypassBannedChecks" => "N",
                "bypassHeaderChecks" => "N",
                "virusQuarantineTo" => null,
                "spamQuarantineTo" => null,
                "bannedQuarantineTo" => null,
                "badHeaderQuarantineTo" => null,
                "uncheckedQuarantineTo" => null,
                "cleanQuarantineTo" => null,
                "archiveQuarantineTo" => null,
                "spamTagLevel" => null,
                "spamTag2Level" => null,
                "spamTag3Level" => null,
                "spamKillLevel" => null,
                "spamDsnCutoffLevel" => null,
                "spamQuarantineCutoffLevel" => null,
                "spamSubjectTag" => null,
                "spamSubjectTag2" => null,
                "spamSubjectTag3" => null,
                "addrExtensionVirus" => null,
                "addrExtensionSpam" => null,
                "addrExtensionBanned" => null,
                "addrExtensionBadHeader" => null,
                "warnvirusrecip" => "N",
                "warnbannedrecip" => "N",
                "warnbadhrecip" => "N",
                "newvirusAdmin" => null,
                "virusAdmin" => null,
                "spamAdmin" => null,
                "bannedAdmin" => null,
                "badHeaderAdmin" => null,
                "messageSizeLimit" => null,
                "bannedRulenames" => null,
                "disclaimerOptions" => null,
                "forwardMethod" => null,
                "saUserconf" => null,
                "saUsername" => null,
            ],
        ];
    }
}
