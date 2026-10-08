<?php

namespace App\Tests\MessageHandler;

use App\Entity\UnsubscribeRequest;
use App\Message\SendOneClickUnsubscribe;
use App\MessageHandler\SendOneClickUnsubscribeHandler;
use App\Repository\UnsubscribeRequestRepository;
use App\Tests\Factory\DomainFactory;
use App\Tests\Factory\UnsubscribeRequestFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\FactoryHelper;
use App\Tests\MessageHelper;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SendOneClickUnsubscribeHandlerTest extends KernelTestCase
{
    use Factories;
    use FactoryHelper;
    use MessageHelper;
    use ResetDatabase;

    /**
     * NoPrivateNetworkHttpClient resolves the host names to check their IP:
     * a public IP is used so that no DNS resolution is needed in the tests
     * (the MockHttpClient never sends any request).
     */
    private const URL = 'https://1.1.1.1/unsubscribe/abc';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testInvokeMarksRequestAsSent(): void
    {
        $request = $this->createPendingRequest();
        $response = new MockResponse('', ['http_code' => 200]);
        $httpClient = new MockHttpClient($response);
        $handler = $this->buildHandler($httpClient);

        //handle the message to send the one-click unsubscribe request
        $handler(new SendOneClickUnsubscribe((int) $request->getId(), self::URL));

        self::assertSame(UnsubscribeRequest::STATUS_SENT, $this->reload($request)->getStatus());
        self::assertSame(1, $httpClient->getRequestsCount());
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::URL, $response->getRequestUrl());
        self::assertSame('List-Unsubscribe=One-Click', $response->getRequestOptions()['body']);
    }

    public function testInvokeMarksRequestAsFailedOnServerError(): void
    {
        $request = $this->createPendingRequest();
        $handler = $this->buildHandler(new MockHttpClient(new MockResponse('', ['http_code' => 500])));

        $handler(new SendOneClickUnsubscribe((int) $request->getId(), self::URL));

        self::assertSame(UnsubscribeRequest::STATUS_FAILED, $this->reload($request)->getStatus());
    }

    public function testInvokeMarksRequestAsSentOnRedirectionWithoutFollowingIt(): void
    {
        $request = $this->createPendingRequest();
        $httpClient = new MockHttpClient([
            new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Location' => 'https://other.example.com/'],
            ]),
            new MockResponse('', ['http_code' => 200]),
        ]);
        $handler = $this->buildHandler($httpClient);

        $handler(new SendOneClickUnsubscribe((int) $request->getId(), self::URL));

        self::assertSame(UnsubscribeRequest::STATUS_SENT, $this->reload($request)->getStatus());
        self::assertSame(1, $httpClient->getRequestsCount());
    }

    public function testInvokeBlocksPrivateNetwork(): void
    {
        $request = $this->createPendingRequest();
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 200]));
        $handler = $this->buildHandler($httpClient);

        $handler(new SendOneClickUnsubscribe((int) $request->getId(), 'https://127.0.0.1/unsubscribe'));

        self::assertSame(UnsubscribeRequest::STATUS_FAILED, $this->reload($request)->getStatus());
        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function testInvokeIgnoresAlreadyProcessedRequest(): void
    {
        $request = $this->createPendingRequest();
        $request->markAsSent();
        $this->save($request);
        // If the request was sent anyway, the request would be marked as failed
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $handler = $this->buildHandler($httpClient);

        $handler(new SendOneClickUnsubscribe((int) $request->getId(), self::URL));

        self::assertSame(UnsubscribeRequest::STATUS_SENT, $this->reload($request)->getStatus());
        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function testInvokeIgnoresDeletedRequest(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 200]));
        $handler = $this->buildHandler($httpClient);

        $handler(new SendOneClickUnsubscribe(123456, self::URL));

        self::assertSame(0, $httpClient->getRequestsCount());
    }

    private function buildHandler(MockHttpClient $httpClient): SendOneClickUnsubscribeHandler
    {
        $container = static::getContainer();

        return new SendOneClickUnsubscribeHandler(
            $httpClient,
            $container->get(UnsubscribeRequestRepository::class),
            new NullLogger(),
        );
    }

    private function createPendingRequest(): UnsubscribeRequest
    {
        $domain = DomainFactory::createOne();
        $recipient = UserFactory::new()->user($domain)->create();
        $sender = UserFactory::new()->user($domain)->create();
        [$addrS, $addrR] = $this->setupAddresses($sender, $recipient);
        $message = $this->setupMail($addrS, [$addrR]);
        $messageRecipient = $message->getMessageRecipients()->first();
        self::assertNotFalse($messageRecipient);

        return UnsubscribeRequestFactory::new()->forMessageRecipient($messageRecipient)->create([
            'status' => UnsubscribeRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Reload the request from the database, to check what has been saved.
     */
    private function reload(UnsubscribeRequest $request): UnsubscribeRequest
    {
        $this->refresh($request);

        return $request;
    }
}
