<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\SenderRule;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\MessageRecipientFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    public function testListUntreatedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);

        $crawler = $client->request(Request::METHOD_GET, '/message');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListBannedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::BANNED);

        $crawler = $client->request(Request::METHOD_GET, '/message/banned');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListAuthorizedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);

        $crawler = $client->request(Request::METHOD_GET, '/message/authorized');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListDeletedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::DELETED);

        $crawler = $client->request(Request::METHOD_GET, '/message/delete');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListRestoredMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::RESTORED);

        $crawler = $client->request(Request::METHOD_GET, '/message/restored');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListSpamMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::SPAMMED);

        $crawler = $client->request(Request::METHOD_GET, '/message/spam');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testListVirusMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::VIRUS);

        $crawler = $client->request(Request::METHOD_GET, '/message/virus');

        $messages = $crawler
            ->filter('td[data-title="Subject"]')
            ->each(fn($node) => trim($node->text()));
        self::assertCount(1, $messages);
        self::assertSame('test', $messages[0]);
    }

    public function testMessageStatsCount(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $this->setupMail($addrS, $addrR, status: MessageStatus::SPAMMED);
        $this->setupMail($addrS, $addrR, status: MessageStatus::SPAMMED);
        $this->setupMail($addrS, $addrR, status: MessageStatus::VIRUS);

        $client->request(Request::METHOD_GET, '/message/stats/counts');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
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

    public function testShowEmail(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/show/';
        $crawler = $client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        $titles = $crawler
            ->filter('div.col-md-4')
            ->each(fn($node) => trim($node->text()));
        $content = $crawler
            ->filter('div.col-md-8')
            ->each(fn($node) => trim($node->text()));
        $result = array_combine($titles, $content);
        $message = MessageFactory::find(['mailId' => $mailId]);
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

    public function testDeleteMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/delete/';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        // Since status update is done directly with Doctrine, we have to refresh entities.
        $this->refresh($message);
        $this->refresh($messageRecipient);
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus()); // TODO: why?
        self::assertSame(MessageStatus::DELETED, $messageRecipient->getStatus());
    }

    public function testAuthorizeMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/authorized';
        $client->request(Request::METHOD_GET, $url);

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

    public function testAuthorizedDomainMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/authorizedDomain';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        self::assertSame(MessageStatus::AUTHORIZED, $message->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient->getStatus());
        $domainRecipient = UserFactory::findBy(['email' => '@' . $recipient->getDomain()]);
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient[0],
            'senderRuleAddress' => $senderRuleAddress,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => ' ', // Mapped from 'accept' by RuleTrait
        ]);
        self::assertCount(1, $domainRule);
    }

    public function testBatchAuthorizeMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/authorized', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
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

    public function testBanMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/banned';
        $client->request(Request::METHOD_GET, $url);

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

    public function testBannedDomainMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        $admin = UserFactory::new()->admin([$domain])->create();
        $client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/bannedDomain';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        self::assertSame(MessageStatus::BANNED, $message->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient->getStatus());
        $domainRecipient = UserFactory::findBy(['email' => '@' . $recipient->getDomain()]);
        $senderRuleAddress = RuleAddressFactory::findBy([
            'email' => strtolower($sender->getEmail()),
        ])[0];
        $domainRule = SenderRuleFactory::findBy([
            'user' => $domainRecipient[0],
            'senderRuleAddress' => $senderRuleAddress,
            'type' => SenderRule::TYPE_USER,
            'priority' => SenderRule::PRIORITY_USER,
            'wb' => 'B',
        ]);
        self::assertCount(1, $domainRule);
    }

    public function testRestoreMessage(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNRELEASED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $message->setStatus(null);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/restore';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testMarkMessageAsSpam(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $message->setStatus(null);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/markAsSpam';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
    }

    public function testCannotMarkMessageAsSpamWhenLoggedAsUser(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $message->setStatus(null);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/markAsSpam';
        $client->request(Request::METHOD_GET, $url);

        self::assertSame(403, $client->getResponse()->getStatusCode());
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
    }

    public function testCannotMarkReleasedMessageAsSpam(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::RESTORED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $message->setStatus(null);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/markAsSpam';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testMarkMessageAsHam(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::SPAMMED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $message->setStatus(null);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/markAsHam';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame($initialSenderRuleCount, SenderRuleFactory::count());
        self::assertNull($message->getStatus());
        self::assertSame(MessageStatus::RESTORED, $messageRecipient->getStatus());
    }

    public function testShowMessageDetail(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $message = MessageFactory::find(['mailId' => $mailId]);

        $url = '/message/0/' . $mailId . '/' . $addrR->getId() . '/content';
        $crawler = $client->request(Request::METHOD_GET, $url);

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
        $crawler = $client->request(Request::METHOD_GET, $iframeSrc->attr('src'));
        self::assertStringContainsString(
            $sender->getEmail(),
            $crawler->filter('body')->text()
        );
        self::assertStringContainsString(
            $message->getSubject(),
            $crawler->filter('body')->text()
        );
    }

    public function testGetMessageReleaseStatus(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::AUTHORIZED);
        $mailRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $now = new DateTimeImmutable();
        $endDate = $now;
        $startDate = $now->sub(new DateInterval('PT1M'));
        $mailRecipient->setAmavisReleaseStartedAt($startDate);
        $mailRecipient->setAmavisReleaseEndedAt($endDate);

        $url = '/message/0/' . $mailId . '/' . $mailRecipient->getRseqnum() . '/release-status';
        $client->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        $json = json_decode($content, true);
        self::assertTrue($json['released']);
        self::assertEquals($startDate, new DateTimeImmutable($json['releaseStartedAt']['date']));
        self::assertEquals($endDate, new DateTimeImmutable($json['releaseEndedAt']['date']));
    }

    public function testBatchAuthorizedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/authorized', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
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

    public function testBatchBannedMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/banned', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
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

    public function testBatchDeleteMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/restore', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()]),
                json_encode([0, $mailId2, $addrR->getId()]),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
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

    public function testBatchRestoreMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/delete', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
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

    public function testBatchMarkAsSpamMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $recipientAlias = UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/mark%20as%20spam', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $message2->getStatus());
        self::assertSame(MessageStatus::SPAMMED, $messageRecipient2->getStatus());
    }

    public function testBatchMarkAsHamMessages(): void
    {
        $client = static::createClient();
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        UserFactory::new()->user()->create([
            'domain' => $domain,
            'originalUser' => $recipient,
        ]);
        $sender = UserFactory::new()->user()->create([
            'domain' => $domain,
        ]);
        $client->loginUser($recipient);
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $mailId = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $mailId2 = $this->setupMail($addrS, $addrR, status: MessageStatus::UNTREATED);
        $initialMessageCount = MessageFactory::count();
        $initialMessageRecipientCount = MessageRecipientFactory::count();
        $message = MessageFactory::find(['mailId' => $mailId]);
        $messageRecipient = MessageRecipientFactory::find(['mailId' => $mailId]);
        $message2 = MessageFactory::find(['mailId' => $mailId2]);
        $messageRecipient2 = MessageRecipientFactory::find(['mailId' => $mailId2]);

        $client->request(Request::METHOD_POST, '/message/batch/mark%20as%20ham', [
            'id' => [
                json_encode([0, $mailId, $addrR->getId()], JSON_THROW_ON_ERROR),
                json_encode([0, $mailId2, $addrR->getId()], JSON_THROW_ON_ERROR),
            ],
            'massive-actions-form' => [
                '_token' => $this->generateCsrfToken($client, ''),
            ],
        ]);

        self::assertResponseRedirects('/');
        self::assertSame($initialMessageCount, MessageFactory::count());
        self::assertSame($initialMessageRecipientCount, MessageRecipientFactory::count());
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $message2->getStatus());
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }
}
