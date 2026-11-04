<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DomainControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testAdminCanListDomainsButSeeOnlyItsOwn(): void
    {
        $domain1Title = 'test.fr';
        $domain2Title = 'test.it';
        $domain3Title = 'test.com';
        $domain1 = DomainFactory::createOne(['domain' => $domain1Title]);
        $domain2 = DomainFactory::createOne(['domain' => $domain2Title]);
        DomainFactory::createOne(['domain' => $domain3Title]);
        self::assertSame(3, DomainFactory::count());
        $admin = UserFactory::new()->admin([$domain1, $domain2])->create();
        $this->client->loginUser($admin);

        $crawler = $this->client->request(Request::METHOD_GET, '/domain/');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertContains($domain1Title, $titles);
        self::assertContains($domain2Title, $titles);
        self::assertNotContains($domain3Title, $titles);
    }

    public function testSuperAdminCanListDomainsAndSeeAll(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain1Title = 'test.fr';
        $domain2Title = 'test.com';
        DomainFactory::createOne(['domain' => $domain1Title]);
        DomainFactory::createOne(['domain' => $domain2Title]);

        $crawler = $this->client->request(Request::METHOD_GET, '/domain/');

        $titles = $crawler
            ->filter('td[data-title="Title"]')
            ->each(fn ($node) => trim($node->text()));
        self::assertContains($domain1Title, $titles);
        self::assertContains($domain2Title, $titles);
        self::assertSame(2, DomainFactory::count());
    }

    public function testUserCannotCreateDomain(): void
    {
        $user = UserFactory::new()->user()->create();
        $this->client->loginUser($user);

        $payload = $this->createPayload($this->client);
        $this->client->request('POST', '/domain/new', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCannotCreateDomain(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $this->client->loginUser($admin);

        $payload = $this->createPayload($this->client);
        $this->client->request('POST', '/domain/new', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSuperAdminCanCreateDomain(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);

        $payload = $this->createPayload($this->client);
        $this->client->request('POST', '/domain/new', $payload);

        self::assertSame(1, DomainFactory::count());
        $domain = DomainFactory::last();
        self::assertTrue($domain->getActive());
        self::assertEquals($domain->getDomain(), $domain->getDomain());
        self::assertEquals('smtp.test2.fr', $domain->getSrvSmtp());
        self::assertEquals(26, $domain->getSmtpPort());
    }

    public function testSuperAdminCanEditDomain(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();

        $payload = $this->createPayload($this->client, [
            "active" => false,
            "srvSmtp" => "smtp.other.fr",
            "smtpPort" => "27",
            "level" => "2",
            "authorizedSendersSpamLevel" => "10",
        ]);
        $this->client->request('POST', '/domain/' . $domain->getId() . '/edit', $payload);

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

    public function testCreateSenderRuleShouldApplyOnPendingMessages(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user()->create();
        $recipient = UserFactory::new()->user($domain)->create();
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($senderAddress, [$recipientAddress], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $message2 = $this->setupMail($senderAddress, [$recipientAddress], status: MessageStatus::UNTREATED);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        $otherSenderAddress = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain->getDomain()),
            'partitionTag' => 0,
            'email' => 'other@' . $domain->getDomain(),
        ]);
        $otherMessage = $this->setupMail($otherSenderAddress, [$recipientAddress], status: MessageStatus::UNTREATED);
        $otherMessageRecipient = $otherMessage->getMessageRecipients()->first();
        self::assertNotFalse($otherMessageRecipient);
        $initialSenderRulesCount = SenderRuleFactory::count();

        $token = $this->generateCsrfToken($this->client, 'domain-sender-rule');
        $payload = [
            "form" => [
                "email" => $sender->getEmail(),
                "wbRule" => "block",
                "_token" => $token,
            ],
        ];
        $url = '/domain/' . $domain->getId() . '/rules/new';
        $this->client->request('POST', $url, $payload);

        self::assertSame($initialSenderRulesCount + 1, SenderRuleFactory::count());
        // Status is updated for all MessageRecipient of this domain for this sender
        $this->refresh($messageRecipient);
        $this->refresh($otherMessageRecipient);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $message2->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
        // Message from other sender not touched
        self::assertSame(MessageStatus::UNTREATED, $otherMessage->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $otherMessageRecipient->getStatus());
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
