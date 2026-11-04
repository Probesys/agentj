<?php

namespace App\Tests\Controller;

use App\Amavis\MessageStatus;
use App\Entity\Group;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Tests\Factory\AddressFactory;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\GroupFactory;
use App\Tests\Factory\GroupRuleFactory;
use App\Tests\Factory\RuleAddressFactory;
use App\Tests\Factory\SenderRuleFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use App\Tests\SessionHelper;
use App\Util\Url;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GroupRuleControllerTest extends WebTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;
    use SessionHelper;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testCreateGroupRuleShouldApplyOnPendingMessages(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user()->create();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 10,
        ]);
        $recipient1->addGroup($group1);
        $this->save($recipient1);
        $recipient2 = UserFactory::new()->user($domain)->create();
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 1,
        ]);
        $recipient2->addGroup($group2);
        $this->save($recipient2);
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
        $initialGroupRulesCount = GroupRuleFactory::count();

        $token = $this->generateCsrfToken($this->client, 'group-sender-rule-create');
        $payload = [
            "group_rule" => [
                "email" => $sender->getEmail(),
                "wbRule" => "block",
                "_token" => $token,
            ],
        ];
        $url = '/groups/' . $group1->getId() . '/rules/new';
        $this->client->request('POST', $url, $payload);

        self::assertSame($initialGroupRulesCount + 1, GroupRuleFactory::count());
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        // MessageRecipient of User member of Group 1 banned
        self::assertSame(MessageStatus::BANNED, $messageRecipient1->getStatus());
        // MessageRecipient of User member of Group 2 untouched
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testCreateGroupRuleShouldNotApplyOnPendingMessagesWhenPriorityIsNotHighest(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user($domain)->create();
        $otherSender = UserFactory::new()->user($domain)->create();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 10,
        ]);
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 1,
        ]);
        $recipient1->addGroup($group1);
        $recipient1->addGroup($group2);
        $this->save($recipient1);
        $recipient2 = UserFactory::new()->user($domain)->create();
        $group3 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 2,
        ]);
        $recipient2->addGroup($group3);
        $this->save($recipient2);
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
        // Create initial state with a GroupRule that ban $sender as sender for group1.
        // Also set status to MessageRecipient accordingly.
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        GroupRuleFactory::createOne([
            'group' => $group1,
            'ruleAddress' => $senderRuleAddress,
            'wb' => 'B',
        ]);
        // Also create SenderRule (that get synced on GroupRule creation)
        SenderRuleFactory::createOne([
            'user' => $recipient1,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'block',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + $group1->getPriority(),
        ]);
        $messageRecipient1->setStatus(MessageStatus::BANNED);
        $this->save($messageRecipient2);
        $initialGroupRulesCount = GroupRuleFactory::count();

        $token = $this->generateCsrfToken($this->client, 'group-sender-rule-create');
        $payload = [
            "group_rule" => [
                "email" => $otherSender->getEmail(),
                "wbRule" => "allow",
                "_token" => $token,
            ],
        ];
        $url = '/groups/' . $group2->getId() . '/rules/new';
        $this->client->request('POST', $url, $payload);

        self::assertSame($initialGroupRulesCount + 1, GroupRuleFactory::count());
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        // MessageRecipient of User member of Group 1 not updated as Group 1 is more prioritized than Group 2
        self::assertSame(MessageStatus::BANNED, $messageRecipient1->getStatus());
        // MessageRecipient of User member of Group 3 untouched
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
    }

    public function testUpdateGroupRuleShouldApplyOnPendingMessages(): void
    {
        $superAdmin = UserFactory::new()->superAdmin()->create();
        $this->client->loginUser($superAdmin);
        $domain = DomainFactory::createOne();
        $sender = UserFactory::new()->user()->create();
        $recipient1 = UserFactory::new()->user($domain)->create();
        $group1 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 10,
        ]);
        $recipient1->addGroup($group1);
        $this->save($recipient1);
        $recipient2 = UserFactory::new()->user($domain)->create();
        $group2 = GroupFactory::createOne([
            'domain' => $domain,
            'priority' => 1,
        ]);
        $recipient2->addGroup($group2);
        $this->save($recipient2);
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
        // Create initial state with a GroupRule that ban $sender as sender for group1.
        // Also set status to MessageRecipient accordingly.
        $senderRuleAddress = RuleAddressFactory::findOrCreate([
            'priority' => 6,
            'email' => $sender->getEmail(),
        ]);
        GroupRuleFactory::createOne([
            'group' => $group1,
            'ruleAddress' => $senderRuleAddress,
            'wb' => 'B',
        ]);
        $messageRecipient1->setStatus(MessageStatus::BANNED);
        $this->save($messageRecipient1);
        // Also create SenderRule (that get synced on GroupRule creation).
        $rule = SenderRuleFactory::createOne([
            'user' => $recipient1,
            'senderRuleAddress' => $senderRuleAddress,
            'wb' => 'block',
            'type' => SenderRule::TYPE_GROUP,
            'priority' => SenderRule::PRIORITY_GROUP + $group1->getPriority(),
            'group' => $group1,
        ]);
        $initialGroupRulesCount = GroupRuleFactory::count();

        $token = $this->generateCsrfToken($this->client, 'group-sender-rule-edit');
        $payload = [
            "group_rule" => [
                "email" => $sender->getEmail(),
                "wbRule" => "allow",
                "_token" => $token,
            ],
        ];
        $url = '/groups/' . $group1->getId() . '/rules/' . $senderRuleAddress->getId() . '/edit';
        $this->client->request('POST', $url, $payload);

        self::assertSame($initialGroupRulesCount, GroupRuleFactory::count());
        $this->refresh($messageRecipient1);
        $this->refresh($messageRecipient2);
        self::assertSame(MessageStatus::UNTREATED, $message->getStatus());
        // MessageRecipient of User member of Group 1 was banned, but updated to authorized
        self::assertSame(MessageStatus::AUTHORIZED, $messageRecipient1->getStatus());
        // MessageRecipient of User member of Group 2 untouched
        self::assertSame(MessageStatus::UNTREATED, $messageRecipient2->getStatus());
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
