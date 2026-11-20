<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Entity\SenderRule;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\PolicyFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DomainControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
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

    public function testCreateDomainRuleShouldApplyOnPendingMessages(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($otherDomain)->create();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        [$senderAddress, $recipientAddress1] = $this->setupAddresses($sender, $recipient1);
        $recipientAddress2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $senderAddress,
            [$recipientAddress1,$recipientAddress2],
            status: MessageStatus::UNTREATED,
        );
        $messageRecipient1 = $this->getMessageRecipient($message, 1);
        $messageRecipient2 = $this->getMessageRecipient($message, 2);
        $initialSenderRuleCont = SenderRuleFactory::count();

        $token = $this->generateCsrfToken($client, 'domain-sender-rule-create');
        $payload = [
            "form" => [
                "email" => $sender->getEmail(),
                "wbRule" => "block",
                "_token" => $token,
            ],
        ];
        $url = '/domain/' . $domain->getId() . '/rules/new';
        $client->request('POST', $url, $payload);

        // SenderRule for domain has been created
        self::assertSame($initialSenderRuleCont + 1, SenderRuleFactory::count());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ])[0];
        $domainUser = UserFactory::findBy(['email' => '@' . $domain])[0];
        $updatedSenderRule = SenderRuleFactory::findBy([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'B',
            'type' => null,
            'priority' => SenderRule::PRIORITY_DOMAIN,
        ]);
        self::assertNotEmpty($updatedSenderRule);
        // But Message and MessageRecipient status have been reprocessed for
        // recipient1 that is hosted on this domain.
        // Message and MessageRecipient status remained untouched for
        // recipient2 that is hosted on another domain.
        $this->refresh($message);
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        // MessageRecipient of User member of Group 1 banned
        self::assertSame(MessageStatus::BANNED, $messageRecipient1->getStatus());
        // MessageRecipient of User member of Group 2 untouched
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testCreateDomainRuleShouldNotApplyOnPendingMessagesIfUserSenderRuleExists(): void
    {
        $client = static::createClient();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($otherDomain)->create();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        [$senderAddress, $recipientAddress1] = $this->setupAddresses($sender, $recipient1);
        $recipientAddress2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail(
            $senderAddress,
            [$recipientAddress1,$recipientAddress2],
            status: MessageStatus::UNTREATED,
        );
        $messageRecipient1 = $this->getMessageRecipient($message, 1);
        $messageRecipient2 = $this->getMessageRecipient($message, 2);
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        SenderRuleFactory::createOne([
            'user' => $recipient1,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'block',
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
        ]);
        $message->setStatus(MessageStatus::BANNED);
        $this->save($message);
        $messageRecipient1->setStatus(MessageStatus::BANNED);
        $this->save($messageRecipient1);
        $initialSenderRuleCont = SenderRuleFactory::count();

        $token = $this->generateCsrfToken($client, 'domain-sender-rule-create');
        $payload = [
            "form" => [
                "email" => $sender->getEmail(),
                "wbRule" => "allow",
                "_token" => $token,
            ],
        ];
        $url = '/domain/' . $domain->getId() . '/rules/new';
        $client->request('POST', $url, $payload);

        // SenderRule for domain has been created
        self::assertSame($initialSenderRuleCont + 1, SenderRuleFactory::count());
        $domainUser = UserFactory::findBy(['email' => '@' . $domain])[0];
        $updatedSenderRule = SenderRuleFactory::findBy([
            'user' => $domainUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'W',
            'type' => null,
            'priority' => SenderRule::PRIORITY_DOMAIN,
        ]);
        self::assertNotEmpty($updatedSenderRule);
        // But Message and MessageRecipient status left unchanged
        // as priority of Domain SenderRule have less priority
        // than User SenderRule.
        $this->refresh($message);
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        // MessageRecipient of User member of Group 1 banned
        self::assertSame(MessageStatus::BANNED, $messageRecipient1->getStatus());
        // MessageRecipient of User member of Group 2 untouched
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
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

    private function getMessageRecipient(Message $message, int $rseqnum): MessageRecipient
    {
        $messageRecipient = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === $rseqnum,
        )->first();

        self::assertNotFalse($messageRecipient);

        return $messageRecipient;
    }
}
