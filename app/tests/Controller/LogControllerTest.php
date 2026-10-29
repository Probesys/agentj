<?php

namespace App\Tests\Controller;

use App\Model\LogAction;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\LogFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LogControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testGetIndexDisplaysAllTheEntriesToSuperAdmins(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $domain = DomainFactory::createOne();
        LogFactory::createOne(['mailId' => 'domainMailId', 'domain' => $domain]);
        LogFactory::createOne(['mailId' => 'globalMailId', 'domain' => null]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Audit log');
        self::assertSelectorTextContains('[data-test="logs"]', 'domainMailId');
        self::assertSelectorTextContains('[data-test="logs"]', $domain->getDomain());
        self::assertSelectorTextContains('[data-test="logs"]', 'globalMailId');
    }

    public function testGetIndexDisplaysOnlyTheEntriesOfManagedDomainsToAdmins(): void
    {
        $managedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        LogFactory::createOne(['mailId' => 'managedMailId', 'domain' => $managedDomain]);
        LogFactory::createOne(['mailId' => 'otherMailId', 'domain' => DomainFactory::createOne()]);
        LogFactory::createOne(['mailId' => 'globalMailId', 'domain' => null]);
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_GET, '/logs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="logs"]', 'managedMailId');
        self::assertSelectorTextNotContains('[data-test="logs"]', 'otherMailId');
        self::assertSelectorTextNotContains('[data-test="logs"]', 'globalMailId');
    }

    public function testGetIndexIgnoresTheFilterOnADomainNotManagedByTheAdmin(): void
    {
        $managedDomain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        LogFactory::createOne(['mailId' => 'managedMailId', 'domain' => $managedDomain]);
        LogFactory::createOne(['mailId' => 'otherMailId', 'domain' => $otherDomain]);
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_GET, '/logs', [
            'log_filter' => ['domain' => $otherDomain->getId()],
        ]);

        // The form is invalid: the filters are not applied
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[data-test="logs"]', 'managedMailId');
        self::assertSelectorTextNotContains('[data-test="logs"]', 'otherMailId');
        // The other domain is not proposed in the filter
        self::assertSelectorNotExists("#log_filter_domain option[value=\"{$otherDomain->getId()}\"]");
    }

    public function testGetIndexFiltersTheEntries(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        LogFactory::createOne(['mailId' => 'bannedMailId', 'action' => LogAction::Banned->value]);
        LogFactory::createOne(['mailId' => 'restoredMailId', 'action' => LogAction::Restore->value]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs', [
            'log_filter' => ['action' => LogAction::Banned->value],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="logs"]', 'bannedMailId');
        self::assertSelectorTextNotContains('[data-test="logs"]', 'restoredMailId');
    }

    public function testGetShowDisplaysTheDetailsAsText(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $details = '<script>alert("details")</script>' . str_repeat('long details ', 20);
        $log = LogFactory::createOne(['details' => $details]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, "/logs/{$log->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-test="details"] script');
        self::assertSelectorTextSame('[data-test="details"]', trim($details));
    }

    public function testGetShowDisplaysTheEntriesOfManagedDomainsToAdmins(): void
    {
        $managedDomain = DomainFactory::createOne();
        $admin = UserFactory::new()->admin([$managedDomain])->create();
        $log = LogFactory::createOne(['domain' => $managedDomain]);
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_GET, "/logs/{$log->getId()}");

        self::assertResponseIsSuccessful();
    }

    public function testGetShowIsForbiddenToAdminsForTheEntriesOfOtherDomains(): void
    {
        $admin = UserFactory::new()->admin([DomainFactory::createOne()])->create();
        $otherLog = LogFactory::createOne(['domain' => DomainFactory::createOne()]);
        $globalLog = LogFactory::createOne(['domain' => null]);
        $this->client->loginUser($admin);

        $this->client->request(Request::METHOD_GET, "/logs/{$otherLog->getId()}");
        self::assertResponseStatusCodeSame(403);

        $this->client->request(Request::METHOD_GET, "/logs/{$globalLog->getId()}");
        self::assertResponseStatusCodeSame(403);
    }

    public function testGetIndexDisplaysTheUsernameOfLocalAdminsWithoutEmail(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create(['username' => 'admin', 'fullname' => null]);
        LogFactory::createOne(['createdBy' => $superAdmin]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('[data-test="log-author"]', 'admin');
    }

    public function testGetIndexSortsTheEntriesByTheGivenColumn(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $bob = UserFactory::createOne(['email' => 'bob@example.org', 'username' => 'bob@example.org']);
        $alice = UserFactory::createOne(['email' => 'alice@example.org', 'username' => 'alice@example.org']);

        LogFactory::createOne([
            'mailId' => 'aliceMailId',
            'createdBy' => $alice,
            'created' => new \DateTime('2026-10-01'),
        ]);
        LogFactory::createOne([
            'mailId' => 'bobMailId',
            'createdBy' => $bob,
            'created' => new \DateTime('2026-10-02'),
        ]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs');
        self::assertSame(['bobMailId', 'aliceMailId'], $this->displayedMailIds());

        $this->client->request(Request::METHOD_GET, '/logs', ['sort' => 'u.username', 'direction' => 'asc']);
        self::assertResponseIsSuccessful();
        self::assertSame(['aliceMailId', 'bobMailId'], $this->displayedMailIds());

        $this->client->request(Request::METHOD_GET, '/logs', ['sort' => 'l.id', 'direction' => 'asc']);
        self::assertResponseIsSuccessful();
        self::assertSame(['aliceMailId', 'bobMailId'], $this->displayedMailIds());
    }

    public function testGetIndexIgnoresTheForgedSortParameters(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        LogFactory::createOne(['mailId' => 'someMailId']);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs', [
            'sort' => 'x.y',
            'direction' => 'asc',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="logs"]', 'someMailId');
    }

    public function testGetIndexIgnoresTheSortOnNotAllowedColumns(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        LogFactory::createOne([
            'mailId' => 'oldMailId',
            'details' => 'b',
            'created' => new \DateTime('2026-10-01'),
        ]);
        LogFactory::createOne([
            'mailId' => 'newMailId',
            'details' => 'a',
            'created' => new \DateTime('2026-10-02'),
        ]);
        $this->client->loginUser($superAdmin);

        $this->client->request(Request::METHOD_GET, '/logs', [
            'sort' => 'l.details',
            'direction' => 'asc',
        ]);

        self::assertResponseIsSuccessful();
        // Sorted by creation date instead of details
        self::assertSame(['oldMailId', 'newMailId'], $this->displayedMailIds());
    }

    public function testGetIndexIsForbiddenToUsers(): void
    {
        $user = UserFactory::new()->user(DomainFactory::createOne())->create();
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, '/logs');

        self::assertResponseStatusCodeSame(403);
    }

    public function testGetShowIsForbiddenToUsers(): void
    {
        $user = UserFactory::new()->user(DomainFactory::createOne())->create();
        $log = LogFactory::createOne(['domain' => $user->getDomain()]);
        $this->client->loginUser($user);

        $this->client->request(Request::METHOD_GET, "/logs/{$log->getId()}");

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return string[]
     */
    private function displayedMailIds(): array
    {
        return $this->client->getCrawler()
            ->filter('[data-test="log-mail-id"]')
            ->each(fn ($node): string => trim($node->text()));
    }
}
