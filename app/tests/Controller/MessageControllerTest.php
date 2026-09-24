<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\SenderRule;
use App\Util\Url;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\LogHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use LogHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->translator = self::getContainer()->get(TranslatorInterface::class);
    }

    public function testUserCanHtmlBodyIsSanitized(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $body = <<<'HTML'
            <strong>Security test</strong>
            <p>If this message is printed correcly, your email client is well configured.</p>
            <img src="x" onerror="alert('Demo XSS')">
            <a hrel="https://threat.security.com/exploit">More information here.</a>
            The security team
        HTML;
        $message = $this->setupMail(
            $senderAddress,
            [$recipientAddress],
            body: $body,
            status: MessageStatus::AUTHORIZED,
        );
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $url = sprintf(
            "/message/%s/%s/%s/iframe-content",
            $message->getPartitionTag(),
            $message->getMailId(),
            $messageRecipient->getRseqnum(),
        );
        $this->client->request(Request::METHOD_GET, $url);

        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<strong>Security test</strong>', $content);
        self::assertStringNotContainsString('<img', $content);
        self::assertStringContainsString('[IMAGE]', $content);
        self::assertStringNotContainsString('onerror=', $content);
        self::assertStringContainsString('[URL] More information here.', $content);
    }

    public function testUserCanListItsUntreatedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsBannedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::BANNED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/banned');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsAuthorizedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/authorized');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsDeletedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::DELETED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/delete');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsRestoredMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::RESTORED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/restored');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsSpamMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/spam');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanListItsVirusMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::VIRUS);

        $crawler = $this->client->request(Request::METHOD_GET, '/message/virus');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testUserCanGetMessageStatsCount(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $this->setupMail($addrS, [$addrR], status: MessageStatus::VIRUS);

        $this->client->request(Request::METHOD_GET, '/message/stats/counts');

        self::assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertJsonStringEqualsJsonString(
            '{
                "authorized": 1,
                "banned": 0,
                "deleted": 0,
                "restored": 0,
                "spammed": 2,
                "untreated": 1,
                "virus": 1
            }',
            $content,
        );
    }

    public function testUserCanShowItsMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/show/';
        $crawler = $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('div.col-md-4')
            ->each(fn($node) => trim($node->text()));
        $content = $crawler
            ->filter('div.col-md-8')
            ->each(fn($node) => trim($node->text()));
        $result = array_combine($titles, $content);
        $date = DateTimeImmutable::createFromFormat(
            'Ymd\THis\Z',
            $message->getTimeIso(),
            new DateTimeZone('UTC'),
        );
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        $date = $date->setTimezone(new DateTimeZone('Europe/Paris'));
        $dateFormatted = $date->format('n/j/y, g:i') . "\u{202F}" . $date->format('A');
        $authReqDate = new DateTimeImmutable()->setTimestamp($message->getSendCaptcha());
        $authReqDateFormatted = $authReqDate->format('n/j/y, g:i') . "\u{202F}" . $authReqDate->format('A');
        $recipient = $message->getMessageRecipients()->first();
        $spamRating = $recipient !== false
            ? (string)$recipient->getBspamLevel()
            : '';
        $expected = [
            'Date' => $dateFormatted,
            'Sender' => $addrS->getEmail(),
            'Recipient' => $addrR->getEmail(),
            'Spam rating' => $spamRating,
            'Authentication request sent the :' => $authReqDateFormatted,
            'Mailing list?' => 'No.',
            'Status' => 'Authorized',
        ];
        self::assertSame($expected, $result);
    }

    public function testUserCanDeleteItsMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/delete/';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        // Since status update is done directly with Doctrine, we have to refresh entities.
        $this->refresh($message);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::DELETED, $messageRecipient->getStatus());
    }

    public function testSecondRecipientCannotDeleteMessageForFirstRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::AUTHORIZED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);

        $url = '/message/0/' . $message->getMailId() . '/' .  $messageRecipient1->getRseqnum() . '/delete/';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        // Since status update is done directly with Doctrine, we have to refresh entities.
        $this->refresh($message);
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    public function testUserCanAuthorizeItsMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/authorized';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 2, SenderRuleFactory::count());
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $recipientRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipient,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $recipientRule);
        $aliasRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipientAlias,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $aliasRule);
    }

    public function testSecondRecipientCannotAuthorizeMessageForFirstRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient1,
        ]);
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient1->getRseqnum() . '/authorized';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
    }

    public function testAuthorizeMessagesFromSameSenderWithDifferentCase(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create([
            'email' => 'AddressCaseTest@' . $domain->getDomain(),
        ]);
        $this->client->loginUser($recipient);
        [$senderAddress, $recipientAddress] = $this->setupAddresses($sender, $recipient);
        $lowercaseSenderAddress = AddressFactory::createOne([
            'domain' => $senderAddress->getDomain(),
            'partitionTag' => 0,
            'email' => strtolower($senderAddress->getEmail()),
        ]);
        $message = $this->setupMail($senderAddress, [$recipientAddress], status: MessageStatus::UNTREATED);
        $lowercaseMessage = $this->setupMail(
            $lowercaseSenderAddress,
            [$recipientAddress],
            status: MessageStatus::UNTREATED,
        );
        $messageRecipient = $message->getMessageRecipients()->first();
        $lowercaseMessageRecipient = $lowercaseMessage->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        self::assertNotFalse($lowercaseMessageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/authorized';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $lowercaseMessageRecipient->getStatus());
        self::assertSame(1, RuleAddressFactory::count([
            'email' => strtolower($senderAddress->getEmail()),
        ]));
    }

    public function testAdminCanAuthorizedDomainMessageForItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$otherDomain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient2->getRseqnum() . '/authorizedDomain';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
        // No SenderRule has been created for other domain
        $domainRecipient1 = UserFactory::findBy(['email' => '@' . $recipient1->getDomain()]);
        $senderRuleAddress1 = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient1[0],
            'senderRuleAddress' => $senderRuleAddress1,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(0, $domainRule);
        // A SenderRule has been created for admin's domain
        $domainRecipient2 = UserFactory::findBy(['email' => '@' . $recipient2->getDomain()]);
        $senderRuleAddress2 = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient2[0],
            'senderRuleAddress' => $senderRuleAddress2,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $domainRule);
    }

    public function testAdminCannotAuthorizedDomainMessageForOtherDomain(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient2->getRseqnum() . '/authorizedDomain';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testAdminCanBatchAuthorizeMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/authorized', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 2, SenderRuleFactory::count());
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $message2->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $recipientRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipient,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $recipientRule);
        $aliasRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipientAlias,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $aliasRule);
    }

    public function testUserCanBanItsMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/banned';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient->getStatus());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $recipientRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipient,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $recipientRule);
        $aliasRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipientAlias,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $aliasRule);
    }

    public function testSecondRecipientCannotBanMessageForFirstRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient1->getRseqnum() . '/banned';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
    }

    public function testAdminCanBannedDomainMessageForItsDomain(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$otherDomain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient2->getRseqnum() . '/bannedDomain';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
        // No SenderRule has been created for other domain
        $domainRecipient1 = UserFactory::findBy(['email' => '@' . $recipient1->getDomain()]);
        $senderRuleAddress1 = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient1[0],
            'senderRuleAddress' => $senderRuleAddress1,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(0, $domainRule);
        // A SenderRule has been created for admin's domain
        $domainRecipient2 = UserFactory::findBy(['email' => '@' . $recipient2->getDomain()]);
        $senderRuleAddress2 = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient2[0],
            'senderRuleAddress' => $senderRuleAddress2,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $domainRule);
    }

    public function testAdminCannotBannedDomainMessageForOtherDomain(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $messageRecipient2 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 2,
        )->first();
        self::assertNotFalse($messageRecipient2);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient2->getRseqnum() . '/bannedDomain';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient1->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUserCanRestoreItsMessage(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNRELEASED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/restore';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testSecondRecipientCannotRestoreMessageForFirstRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNRELEASED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient1->getRseqnum() . '/restore';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::UNRELEASED, $messageRecipient1->getStatus());
    }

    public function testAdminCanMarkMessageAsSpam(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsSpam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
    }

    public function testMarkMessageAsSpamOnlyAffectsRecipientsOfManagedDomains(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsSpam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $otherMessageRecipient->getStatus());
    }

    public function testUserCannotMarkMessageAsSpam(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsSpam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testAdminCannotMarkReleasedMessageAsSpam(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::RESTORED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsSpam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testAdminCanMarkMessageAsHam(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message->setStatus(null);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsHam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testMarkMessageAsHamOnlyAffectsRecipientsOfManagedDomains(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::SPAMMED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/markAsHam';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $otherMessageRecipient->getStatus());
    }

    public function testUserCanShowItsMessageDetail(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/content';
        $crawler = $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        $recover = $crawler
            ->filter('a[data-dialog-title="Recover the message"]')
            ->first();
        self::assertSame('Recover', $recover->text());
        $authorize = $crawler
            ->filter('a[data-dialog-title="Authorize the sender"]')
            ->first();
        self::assertSame('Authorize', $authorize->text());
        $ban = $crawler
            ->filter('a[data-dialog-title="Ban the sender"]')
            ->first();
        self::assertSame('Ban', $ban->text());
        $delete = $crawler
            ->filter('a[data-dialog-title="Delete the message"]')
            ->first();
        self::assertSame('Delete', $delete->text());
        $iframeSrc = $crawler->filter('iframe');
        $crawler = $this->client->request(Request::METHOD_GET, $iframeSrc->attr('src'));
        self::assertStringContainsString(
            $sender->getEmail(),
            $crawler->filter('body')->text()
        );
        self::assertStringContainsString(
            $message->getSubject(),
            $crawler->filter('body')->text()
        );
    }

    public function testSecondRecipientCannotShowMessageDetailForSecondRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient1->getRseqnum() . '/content';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUserCanGetItsMessageReleaseStatus(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::AUTHORIZED);
        $mailRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($mailRecipient);
        $now = new DateTimeImmutable();
        $endDate = $now;
        $startDate = $now->sub(new DateInterval('PT1M'));
        $mailRecipient->setAmavisReleaseStartedAt($startDate);
        $mailRecipient->setAmavisReleaseEndedAt($endDate);

        $url = '/message/0/' . $message->getMailId() . '/' . $mailRecipient->getRseqnum() . '/release-status';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $json = json_decode($content, true);
        self::assertTrue($json['released']);
        self::assertEquals($startDate, new DateTimeImmutable($json['releaseStartedAt']['date']));
        self::assertEquals($endDate, new DateTimeImmutable($json['releaseEndedAt']['date']));
    }

    public function testSecondRecipientCannotGetMessageReleaseStatusForFirstRecipient(): void
    {
        $domain = DomainFactory::createOne();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient2);
        [$addrS, $addrR1] = $this->setupAddresses($sender, $recipient1);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($recipient2->getDomain()->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR1,$addrR2], status: MessageStatus::UNTREATED);
        $messageRecipient1 = $message->getMessageRecipients()->filter(
            fn ($messageRcpt) => $messageRcpt->getRseqnum() === 1,
        )->first();
        self::assertNotFalse($messageRecipient1);
        $now = new DateTimeImmutable();
        $endDate = $now;
        $startDate = $now->sub(new DateInterval('PT1M'));
        $messageRecipient1->setAmavisReleaseStartedAt($startDate);
        $messageRecipient1->setAmavisReleaseEndedAt($endDate);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient1->getRseqnum() . '/release-status';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanBatchAuthorizedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/authorized', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 2, SenderRuleFactory::count());
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $message2->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $recipientRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipient,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $recipientRule);
        $aliasRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipientAlias,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $aliasRule);
    }

    public function testAdminCanBatchBannedMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/banned', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 2, SenderRuleFactory::count());
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::BANNED, $message2->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $recipientRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipient,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $recipientRule);
        $aliasRule = SenderRuleFactory::findBy([
            'senderRuleAddress' => $senderRuleAddress,
            'user' => $recipientAlias,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $aliasRule);
    }

    public function testAdminCanBatchRestoreMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/restore', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()]),
                json_encode([0, $message2->getMailId(), $addrR->getId()]),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $message2->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
    }

    public function testAdminCanBatchDeleteMessages(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/delete', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        // Since status update is done directly with Doctrine, we have to refresh entities.
        $this->refresh($message);
        $this->refresh($messageRecipient);
        $this->refresh($message2);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::DELETED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $message2->getStatus());
        self::assertSame(MessageStatus::DELETED, $messageRecipient2->getStatus());
    }

    public function testAdminCanBatchMarkAsSpamMessages(): void
    {
        $domain = DomainFactory::createOne();
        $domain2 = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain2)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain, $domain2])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain2->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::UNTREATED);
        $message2 = $this->setupMail($addrS, [$addrR2], status: MessageStatus::UNTREATED);
        $otherMessageRecipient2 = $this->setupMailRecipient($message2, $addrOtherR, 2, MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/mark%20as%20spam', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR2->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient2->getStatus());
        // Recipients of a domain not managed by the admin are left untouched.
        self::assertSame(MessageStatus::UNTREATED, $otherMessageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $otherMessageRecipient2->getStatus());
    }

    public function testBatchMarkAsSpamMessagesAsSuperAdmin(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $this->client->request(Request::METHOD_POST, '/message/batch/mark%20as%20spam', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        // A super admin manages every domain, so all recipients are affected.
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $otherMessageRecipient->getStatus());
    }

    public function testCannotBatchMarkAsSpamMessagesWhenLoggedAsUser(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $this->client->request(Request::METHOD_POST, '/message/batch/mark%20as%20spam', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testAdminCanBatchMarkAsHamMessages(): void
    {
        $domain = DomainFactory::createOne();
        $domain2 = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $recipient2 = UserFactory::new()->user($domain2)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain, $domain2])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrR2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain2->getDomain()),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::SPAMMED);
        $message2 = $this->setupMail($addrS, [$addrR2], status: MessageStatus::SPAMMED);
        $otherMessageRecipient2 = $this->setupMailRecipient($message2, $addrOtherR, 2, MessageStatus::SPAMMED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);

        $this->client->request(Request::METHOD_POST, '/message/batch/mark%20as%20ham', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $message2->getMailId(), $addrR2->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
               '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient2->getStatus());
        // Recipients of a domain not managed by the admin are left untouched.
        self::assertSame(MessageStatus::SPAMMED, $otherMessageRecipient->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $otherMessageRecipient2->getStatus());
    }

    public function testBatchMarkAsHamMessagesAsSuperAdmin(): void
    {
        $domain = DomainFactory::createOne();
        $otherDomain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $otherRecipient = UserFactory::new()->user($otherDomain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $addrOtherR = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($otherDomain->getDomain()),
            'partitionTag' => 0,
            'email' => $otherRecipient->getEmail(),
        ]);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::SPAMMED);
        $otherMessageRecipient = $this->setupMailRecipient($message, $addrOtherR, 2, MessageStatus::SPAMMED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $this->client->request(Request::METHOD_POST, '/message/batch/mark%20as%20ham', [
            'id' => [
                json_encode([0, $message->getMailId(), $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($this->client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        // A super admin manages every domain, so all recipients are affected.
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::RESTORED, $otherMessageRecipient->getStatus());
    }

    public function testAdminShouldBeWarnedBeforeAccessingUserEmail(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/content';
        $crawler = $this->client->request(Request::METHOD_GET, $url);

        $pageBody = $crawler->filter('body')->text();
        $expectedContent = $this->translator->trans('Entities.Message.warning.content', locale: 'en');
        $this->assertStringContainsString(
            $expectedContent,
            $pageBody,
        );
        $this->assertStringNotContainsString(
            'test subject',
            $pageBody,
        );
        $this->assertStringNotContainsString(
            'test body',
            $pageBody,
        );
    }

    public function testUserActionShouldBeLoggedWhenAccessingUserEmail(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $this->client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::UNTREATED,
            subject: 'test subject',
            body: 'test body',
        );
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $logs = $this->getLogs();
        self::assertEmpty($logs);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/iframe-content';
        $this->client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        // Assert on log content
        $logs = $this->getLogs();
        self::assertCount(1, $logs);
        $log = $logs[0];
        self::assertSame('message content displayed', $log['action']);
        self::assertSame($message->getMailId(), $log['mailId']);
        $expectedDetails = sprintf(
            "recipient=%s user=%s (%d)",
            $messageRecipient->getAddress()->getEmail(),
            $admin->getUserIdentifier(),
            $admin->getId(),
        );
        self::assertSame($expectedDetails, $log['details']);
        self::assertNotNull($log['created']);
    }

    public function testUserActionShouldNotBeLoggedWhenAccessingItsEmail(): void
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create([
            'domain' => $domain,
        ]);
        $this->client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail(
            $addrS,
            [$addrR],
            status: MessageStatus::AUTHORIZED,
            subject: 'test subject',
            body: 'test body',
        );
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        $logs = $this->getLogs();
        self::assertEmpty($logs);

        $url = '/message/0/' . $message->getMailId() . '/' . $messageRecipient->getRseqnum() . '/content';
        $crawler = $this->client->request(Request::METHOD_GET, $url);

        // Assert on page content
        $pageBody = $crawler->filter('body')->text();
        // FIXME: empty body
        // $this->assertStringContainsString(
        //     'test subject',
        //     $pageBody,
        // );
        // $this->assertStringContainsString(
        //     'test body',
        //     $pageBody,
        // );
        // Assert on log content
        $logs = $this->getLogs();
        self::assertCount(0, $logs);
    }
}
