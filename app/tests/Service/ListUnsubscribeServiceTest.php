<?php

namespace App\Tests\Service;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Entity\User;
use App\Exception\CannotUnsubscribeException;
use App\Message\SendOneClickUnsubscribe;
use App\Model\UnsubscribeMethods;
use App\Repository\MessageListMetadataRepository;
use App\Repository\UnsubscribeRequestRepository;
use App\Service\ListUnsubscribeParser;
use App\Service\ListUnsubscribeService;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\MessageListMetadataFactory;
use App\Tests\Factory\UnsubscribeRequestFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListUnsubscribeServiceTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    private const ONE_CLICK_HEADERS = [
        'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>, <mailto:leave@lists.example.com>',
        'list-unsubscribe-post' => 'List-Unsubscribe=One-Click',
    ];

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testUnsubscribeDispatchesOneClickRequest(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(
            self::ONE_CLICK_HEADERS,
            listId: 'news.example.com',
        );
        // The bus is mocked: the message must not be handled, as the handler
        // would send the request to the list server.
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(
                fn (SendOneClickUnsubscribe $message): bool =>
                    $message->url === 'https://lists.example.com/unsubscribe/abc'
            ))
            ->willReturnCallback(fn (object $message): Envelope => new Envelope($message));
        $container = static::getContainer();
        $service = new ListUnsubscribeService(
            new ListUnsubscribeParser(),
            $container->get(MessageListMetadataRepository::class),
            $container->get(UnsubscribeRequestRepository::class),
            $bus,
        );

        $result = $service->unsubscribe($messageRecipient, $user);

        self::assertNull($result->redirectUrl);
        self::assertSame(UnsubscribeMethods::METHOD_ONE_CLICK, $result->request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_PENDING, $result->request->getStatus());
        self::assertSame('news.example.com', $result->request->getListId());
        self::assertSame($user->getId(), $result->request->getRequestedBy()?->getId());
        $savedRequest = $service->getLastRequest($messageRecipient);
        self::assertNotNull($savedRequest);
        self::assertSame($result->request->getId(), $savedRequest->getId());
    }

    public function testUnsubscribeReturnsHttpsRedirectUrl(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage([
            'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>',
        ]);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        $result = $service->unsubscribe($messageRecipient, $user);

        self::assertSame('https://lists.example.com/unsubscribe/abc', $result->redirectUrl);
        self::assertSame(UnsubscribeMethods::METHOD_HTTPS, $result->request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $result->request->getStatus());
    }

    public function testUnsubscribeReturnsMailtoRedirectUrlAsIs(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage([
            'list-unsubscribe' => '<mailto:leave@lists.example.com?subject=stop>',
        ]);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        $result = $service->unsubscribe($messageRecipient, $user);

        self::assertSame('mailto:leave@lists.example.com?subject=stop', $result->redirectUrl);
        self::assertSame(UnsubscribeMethods::METHOD_MAILTO, $result->request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $result->request->getStatus());
    }

    public function testUnsubscribeUnsubscribesTheAliasOfTheUser(): void
    {
        // The message is sent to $alias, an alias of $user
        [$alias, $messageRecipient] = $this->setupListMessage([
            'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>',
        ]);
        $user = UserFactory::new()->user($alias->getDomain())->create();
        $user->addAlias($alias);
        $this->save($user);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertTrue($service->canUnsubscribe($messageRecipient, $user));
        $result = $service->unsubscribe($messageRecipient, $user);

        // The request concerns the address of the alias, made by the user
        self::assertSame($alias->getEmail(), $result->request->getAddress()->getEmail());
        self::assertSame($user->getId(), $result->request->getRequestedBy()?->getId());
    }

    public function testUnsubscribeRecordsTheGivenRequester(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage([
            'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>',
        ]);
        $domain = $user->getDomain();
        self::assertNotNull($domain);
        $admin = UserFactory::new()->admin([$domain])->create();
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        $result = $service->unsubscribe($messageRecipient, $user, $admin);

        // The request concerns the address of the user, made by the admin
        self::assertSame($user->getEmail(), $result->request->getAddress()->getEmail());
        self::assertSame($admin->getId(), $result->request->getRequestedBy()?->getId());
    }

    public function testUnsubscribeFailsIfUserIsNotTheRecipient(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS);
        $otherUser = UserFactory::new()->user($user->getDomain())->create();
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        $this->expectException(CannotUnsubscribeException::class);
        $service->unsubscribe($messageRecipient, $otherUser);
    }

    public function testUnsubscribeReturnsThePendingRequestInsteadOfSendingAgain(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS);
        $pendingRequest = UnsubscribeRequestFactory::new()->forMessageRecipient($messageRecipient)->create([
            'status' => UnsubscribeRequest::STATUS_PENDING,
        ]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');
        $container = static::getContainer();
        $service = new ListUnsubscribeService(
            new ListUnsubscribeParser(),
            $container->get(MessageListMetadataRepository::class),
            $container->get(UnsubscribeRequestRepository::class),
            $bus,
        );

        $result = $service->unsubscribe($messageRecipient, $user);

        self::assertSame($pendingRequest->getId(), $result->request->getId());
        self::assertNull($result->redirectUrl);
        self::assertSame(1, $container->get(UnsubscribeRequestRepository::class)->count([]));
    }

    public function testCanUnsubscribeReturnsFalseIfMessageIsNotFromList(): void
    {
        // The message has list metadata, but Amavis did not detect it as a
        // mailing list message
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS, isMlist: false);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertFalse($service->canUnsubscribe($messageRecipient, $user));
    }

    public function testCanUnsubscribeReturnsFalseForAdmin(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS);
        $domain = $user->getDomain();
        self::assertNotNull($domain);
        $admin = UserFactory::new()->admin([$domain])->create();
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertFalse($service->canUnsubscribe($messageRecipient, $admin));
    }

    public function testCanUnsubscribeReturnsFalseForSpam(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS, status: MessageStatus::SPAMMED);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertFalse($service->canUnsubscribe($messageRecipient, $user));
    }

    public function testCanUnsubscribeReturnsFalseForVirus(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(self::ONE_CLICK_HEADERS, status: MessageStatus::VIRUS);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertFalse($service->canUnsubscribe($messageRecipient, $user));
    }

    public function testGetUnsubscribeMethodsReturnsNullWithoutMetadata(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage(null);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertNull($service->getUnsubscribeMethods($messageRecipient));
        self::assertFalse($service->canUnsubscribe($messageRecipient, $user));
    }

    public function testGetUnsubscribeMethodsReturnsNullWithoutSupportedMethod(): void
    {
        [$user, $messageRecipient] = $this->setupListMessage([
            'list-unsubscribe' => '<http://lists.example.com/unsubscribe>',
        ]);
        $service = static::getContainer()->get(ListUnsubscribeService::class);

        self::assertNull($service->getUnsubscribeMethods($messageRecipient));
        self::assertFalse($service->canUnsubscribe($messageRecipient, $user));
    }

    /**
     * Create a message sent to a new user, with its list metadata (as Amavis
     * would save them), or without metadata if $headers is null.
     *
     * @param ?array{list-unsubscribe?: string, list-unsubscribe-post?: string} $headers
     *
     * @return array{User, MessageRecipient}
     */
    private function setupListMessage(
        ?array $headers,
        ?string $listId = null,
        ?int $status = null,
        bool $isMlist = true,
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
                'listId' => $listId,
                'listUnsubscribe' => $headers['list-unsubscribe'] ?? null,
                'listUnsubscribePost' => $headers['list-unsubscribe-post'] ?? null,
            ]);
        }

        return [$recipient, $messageRecipient];
    }
}
