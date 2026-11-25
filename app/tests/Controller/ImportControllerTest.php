<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\SenderRule;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\GroupRuleFactory;
use App\Tests\Factory\MessageFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ImportControllerTest extends WebTestCase
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

    public function testImportUsersInGroupShouldReprocessPendingEmailsBan(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain)->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $group = GroupFactory::createOne([
            'domain' => $domain,
        ]);
        $recipient2 = UserFactory::new()->user($domain)->create([
            'email' => 'group@' . $domain,
        ]);
        // $recipient2->addGroup($group);
        $admin = UserFactory::new()->admin([$domain])->create();
        // Add a GroupRule to block sender
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        GroupRuleFactory::createOne([
            'group' => $group,
            'ruleAddress' => $senderRuleAddress,
            'wb' => 'block',
        ]);
        $this->client->loginUser($admin);
        // Send an email to recipient
        [$addrSender, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrSender, [$addrRecipient], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        // Send an email to recipient2
        $recipientAddress2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message2 = $this->setupMail($addrSender, [$recipientAddress2], status: MessageStatus::UNTREATED);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        // Get the page to retrieve CSRF token
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/import/users');
        self::assertResponseIsSuccessful();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $initialUserCount = UserFactory::count();

        $path = sys_get_temp_dir() . '/importUsers_' . uniqid() . '.csv';
        file_put_contents(
            $path,
            implode(PHP_EOL, [
                $this->createCsvLine('test', $domain),
                $this->createCsvLine('group', $domain, $group),
            ])
        );
        $form = $crawler->filter('form[name="import"]')->form();
        $values = $form->getPhpValues(); // includes the CSRF token and domain
        $files = [
            'import' => [
                'attachment' => new UploadedFile($path, 'importUsers.csv', 'text/csv', null, true),
            ],
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values, $files);

        self::assertResponseRedirects();
        self::assertSame($initialUserCount + 1, UserFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        // Check that a SenderRule has been added to imported user with group
        $createdGroupUser = UserFactory::findBy(['email' => 'group@' . $domain])[0];
        SenderRuleFactory::assert()->count(1, [
            'user' => $createdGroupUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'B',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + $group->getPriority(),
        ]);
        // MessageRecipientRepository broke the Doctrine cache when we call
        // findSentToGroupMembersByEmail method. That's why we have to query
        // message again.
        $message2 = MessageFactory::findBy([
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
        ])[0];
        self::assertNotFalse($message2);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        // FIXME: should be BANNED too
        // self::assertSame(MessageStatus::BANNED, $message2->getStatus());
        self::assertSame(MessageStatus::BANNED, $messageRecipient2->getStatus());
    }

    public function testImportUsersInGroupShouldReprocessPendingEmailsAuthorizeAllow(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain)->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $group = GroupFactory::createOne([
            'domain' => $domain,
        ]);
        $recipient2 = UserFactory::new()->user($domain)->create([
            'email' => 'group@' . $domain,
        ]);
        // $recipient2->addGroup($group);
        $admin = UserFactory::new()->admin([$domain])->create();
        // Add a GroupRule to block sender
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        GroupRuleFactory::createOne([
            'group' => $group,
            'ruleAddress' => $senderRuleAddress,
            'wb' => 'allow',
        ]);
        $this->client->loginUser($admin);
        // Send an email to recipient
        [$addrSender, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrSender, [$addrRecipient], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        // Send an email to recipient2
        $recipientAddress2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message2 = $this->setupMail($addrSender, [$recipientAddress2], status: MessageStatus::UNTREATED);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        // Get the page to retrieve CSRF token
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/import/users');
        self::assertResponseIsSuccessful();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $initialUserCount = UserFactory::count();

        $path = sys_get_temp_dir() . '/importUsers_' . uniqid() . '.csv';
        file_put_contents(
            $path,
            implode(PHP_EOL, [
                $this->createCsvLine('test', $domain),
                $this->createCsvLine('group', $domain, $group),
            ])
        );
        $form = $crawler->filter('form[name="import"]')->form();
        $values = $form->getPhpValues(); // includes the CSRF token and domain
        $files = [
            'import' => [
                'attachment' => new UploadedFile($path, 'importUsers.csv', 'text/csv', null, true),
            ],
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values, $files);

        self::assertResponseRedirects();
        self::assertSame($initialUserCount + 1, UserFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        // Check that a SenderRule has been added to imported user with group
        $createdGroupUser = UserFactory::findBy(['email' => 'group@' . $domain])[0];
        SenderRuleFactory::assert()->count(1, [
            'user' => $createdGroupUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'W',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + $group->getPriority(),
        ]);
        // MessageRecipientRepository broke the Doctrine cache when we call
        // findSentToGroupMembersByEmail method. That's why we have to query
        // message again.
        $message2 = MessageFactory::findBy([
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
        ])[0];
        self::assertNotFalse($message2);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        self::assertSame(MessageStatus::AUTHORIZED, $message2->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    public function testImportUsersInGroupShouldReprocessPendingEmailsAuthorizeAccept(): void
    {
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain)->create();
        $recipient = UserFactory::new()->user($domain)->create();
        $group = GroupFactory::createOne([
            'domain' => $domain,
        ]);
        $recipient2 = UserFactory::new()->user($domain)->create([
            'email' => 'group@' . $domain,
        ]);
        // $recipient2->addGroup($group);
        $admin = UserFactory::new()->admin([$domain])->create();
        // Add a GroupRule to block sender
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        GroupRuleFactory::createOne([
            'group' => $group,
            'ruleAddress' => $senderRuleAddress,
            'wb' => 'accept',
        ]);
        $this->client->loginUser($admin);
        // Send an email to recipient
        [$addrSender, $addrRecipient] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrSender, [$addrRecipient], status: MessageStatus::UNTREATED);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);
        // Send an email to recipient2
        $recipientAddress2 = AddressFactory::createOne([
            'domain' => Url::reverseDomainName($domain),
            'partitionTag' => 0,
            'email' => $recipient2->getEmail(),
        ]);
        $message2 = $this->setupMail($addrSender, [$recipientAddress2], status: MessageStatus::UNTREATED);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        // Get the page to retrieve CSRF token
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/import/users');
        self::assertResponseIsSuccessful();
        $initialSenderRuleCount = SenderRuleFactory::count();
        $initialUserCount = UserFactory::count();

        $path = sys_get_temp_dir() . '/importUsers_' . uniqid() . '.csv';
        file_put_contents(
            $path,
            implode(PHP_EOL, [
                $this->createCsvLine('test', $domain),
                $this->createCsvLine('group', $domain, $group),
            ])
        );
        $form = $crawler->filter('form[name="import"]')->form();
        $values = $form->getPhpValues(); // includes the CSRF token and domain
        $files = [
            'import' => [
                'attachment' => new UploadedFile($path, 'importUsers.csv', 'text/csv', null, true),
            ],
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values, $files);

        self::assertResponseRedirects();
        self::assertSame($initialUserCount + 1, UserFactory::count());
        self::assertSame($initialSenderRuleCount + 1, SenderRuleFactory::count());
        // Check that a SenderRule has been added to imported user with group
        $createdGroupUser = UserFactory::findBy(['email' => 'group@' . $domain])[0];
        SenderRuleFactory::assert()->count(1, [
            'user' => $createdGroupUser,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => '',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + $group->getPriority(),
        ]);
        // MessageRecipientRepository broke the Doctrine cache when we call
        // findSentToGroupMembersByEmail method. That's why we have to query
        // message again.
        $message2 = MessageFactory::findBy([
            'partitionTag' => $messageRecipient2->getPartitionTag(),
            'mailId' => $messageRecipient2->getMailId(),
        ])[0];
        self::assertNotFalse($message2);
        $messageRecipient2 = $message2->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient2);
        self::assertSame(MessageStatus::AUTHORIZED, $message2->getStatus());
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient2->getStatus());
    }

    private function createCsvLine(string $name, Domain $domain, ?Group $group = null): string
    {
        return implode(';', [
            $name . '@' . $domain,
            $name,
            $name,
            $group?->getName() ?? '',
        ]);
    }
}
