<?php

namespace App\Tests\Security;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Security\UnsubscribeListVoter;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageListMetadataFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UnsubscribeListVoterTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    private const ONE_CLICK_HEADERS = [
        'list-id' => 'news.example.com',
        'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>, <mailto:leave@lists.example.com>',
        'list-unsubscribe-post' => 'List-Unsubscribe=One-Click',
    ];


    public function testRecipientCanUnsubscribe(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $messageRecipient));
    }

    public function testOwnerOfTheRecipientAliasCanUnsubscribe(): void
    {
        // The message is sent to $alias, an alias of $user
        [$alias, $messageRecipient] = $this->setupListMessage();
        $user = UserFactory::new()->user($alias->getDomain())->create();
        $user->addAlias($alias);
        $this->save($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $messageRecipient));
    }

    public function testAdminCannotUnsubscribe(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage();
        $domain = $user->getDomain();
        self::assertNotNull($domain);
        $admin = UserFactory::new()->admin([$domain])->create();

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($admin, $messageRecipient));
    }

    public function testCannotUnsubscribeFromMessageNotFromList(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(isMlist: false);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $messageRecipient));
    }

    public function testCannotUnsubscribeWithoutMetadata(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(headers: null);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $messageRecipient));
    }

    public function testCannotUnsubscribeWithoutUnsubscribeMethod(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(headers: [
            'list-id' => 'news.example.com',
        ]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $messageRecipient));
    }

    public function testCannotUnsubscribeFromSpam(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(status: MessageStatus::SPAMMED);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $messageRecipient));
    }

    public function testCannotUnsubscribeFromVirus(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(status: MessageStatus::VIRUS);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $messageRecipient));
    }

    private function vote(User $user, MessageRecipient $messageRecipient): int
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $voter = self::getContainer()->get(UnsubscribeListVoter::class);

        return $voter->vote($token, $messageRecipient, ['UNSUBSCRIBE_LIST']);
    }

    /**
     * Create a mailing list message sent to a new user.
     *
     * @param ?array{
     *     list-id?: string,
     *     list-unsubscribe?: string,
     *     list-unsubscribe-post?: string,
     * } $headers
     *
     * @return array{User, MessageRecipient}
     */
    private function setupListMessage(
        ?int $status = null,
        bool $isMlist = true,
        ?array $headers = self::ONE_CLICK_HEADERS,
    ): array {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR], status: $status, messageAttributes: [
            'isMlist' => $isMlist,
        ]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        if ($headers !== null) {
            MessageListMetadataFactory::new()->forMessage($message)->create([
                'listId' => $headers['list-id'] ?? null,
                'listUnsubscribe' => $headers['list-unsubscribe'] ?? null,
                'listUnsubscribePost' => $headers['list-unsubscribe-post'] ?? null,
            ]);
        }

        return [$recipient, $messageRecipient];
    }
}
