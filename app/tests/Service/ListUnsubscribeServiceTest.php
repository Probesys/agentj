<?php

namespace App\Tests\Service;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
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
use App\Tests\MessageHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListUnsubscribeServiceTest extends KernelTestCase
{
    use Factories;
    use MessageHelper;
    use ResetDatabase;

    private const ONE_CLICK_HEADERS = [
        'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>, <mailto:leave@lists.example.com>',
        'list-unsubscribe-post' => 'List-Unsubscribe=One-Click',
    ];

    private ListUnsubscribeService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ListUnsubscribeService::class);
    }

    public function testUnsubscribeDispatchesOneClickRequest(): void
    {
        $messageRecipient = $this->setupListMessage(
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

        $request = $service->unsubscribe($messageRecipient);

        self::assertSame(UnsubscribeMethods::METHOD_ONE_CLICK, $request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_PENDING, $request->getStatus());
        self::assertSame('news.example.com', $request->getListId());
        $savedRequest = $service->getLastRequest($messageRecipient);
        self::assertNotNull($savedRequest);
        self::assertSame($request->getId(), $savedRequest->getId());
    }

    public function testUnsubscribeInitiatesHttpsRequest(): void
    {
        $messageRecipient = $this->setupListMessage([
            'list-unsubscribe' => '<https://lists.example.com/unsubscribe/abc>',
        ]);

        $request = $this->service->unsubscribe($messageRecipient);

        self::assertSame(UnsubscribeMethods::METHOD_HTTPS, $request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $request->getStatus());
    }

    public function testUnsubscribeInitiatesMailtoRequest(): void
    {
        $messageRecipient = $this->setupListMessage([
            'list-unsubscribe' => '<mailto:leave@lists.example.com?subject=stop>',
        ]);

        $request = $this->service->unsubscribe($messageRecipient);

        self::assertSame(UnsubscribeMethods::METHOD_MAILTO, $request->getMethod());
        self::assertSame(UnsubscribeRequest::STATUS_INITIATED, $request->getStatus());
    }

    public function testUnsubscribeFailsWithoutUnsubscribeMethod(): void
    {
        $messageRecipient = $this->setupListMessage([
            'list-unsubscribe' => '<http://lists.example.com/unsubscribe>',
        ]);

        $this->expectException(CannotUnsubscribeException::class);
        $this->service->unsubscribe($messageRecipient);
    }

    public function testUnsubscribeReturnsThePendingRequestInsteadOfSendingAgain(): void
    {
        $messageRecipient = $this->setupListMessage(self::ONE_CLICK_HEADERS);
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

        $request = $service->unsubscribe($messageRecipient);

        self::assertSame($pendingRequest->getId(), $request->getId());
        self::assertSame(1, $container->get(UnsubscribeRequestRepository::class)->count([]));
    }

    public function testUnsubscribeSendsAgainIfThePendingRequestIsTooOld(): void
    {
        $messageRecipient = $this->setupListMessage(self::ONE_CLICK_HEADERS);
        // E.g. the handler failed to save the result of the request
        $lostRequest = UnsubscribeRequestFactory::new()->forMessageRecipient($messageRecipient)->create([
            'status' => UnsubscribeRequest::STATUS_PENDING,
            'createdAt' => new \DateTimeImmutable('-25 hours'),
        ]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(fn (object $message): Envelope => new Envelope($message));
        $container = static::getContainer();
        $service = new ListUnsubscribeService(
            new ListUnsubscribeParser(),
            $container->get(MessageListMetadataRepository::class),
            $container->get(UnsubscribeRequestRepository::class),
            $bus,
        );

        $request = $service->unsubscribe($messageRecipient);

        self::assertNotSame($lostRequest->getId(), $request->getId());
        self::assertSame(UnsubscribeRequest::STATUS_PENDING, $request->getStatus());
        self::assertSame(2, $container->get(UnsubscribeRequestRepository::class)->count([]));
    }

    public function testGetUnsubscribeMethodsReturnsNullWithoutMetadata(): void
    {
        $messageRecipient = $this->setupListMessage(null);

        self::assertNull($this->service->getUnsubscribeMethods($messageRecipient));
    }

    public function testGetUnsubscribeMethodsReturnsNullWithoutSupportedMethod(): void
    {
        $messageRecipient = $this->setupListMessage([
            'list-unsubscribe' => '<http://lists.example.com/unsubscribe>',
        ]);

        self::assertNull($this->service->getUnsubscribeMethods($messageRecipient));
    }

    /**
     * Create a message sent to a new user, with its list metadata (as Amavis
     * would save them), or without metadata if $headers is null.
     *
     * @param ?array{list-unsubscribe?: string, list-unsubscribe-post?: string} $headers
     */
    private function setupListMessage(
        ?array $headers,
        ?string $listId = null,
        ?int $status = null,
        bool $isMlist = true,
    ): MessageRecipient {
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

        return $messageRecipient;
    }
}
