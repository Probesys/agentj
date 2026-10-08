<?php

namespace App\MessageHandler;

use App\Message\SendOneClickUnsubscribe;
use App\Repository\UnsubscribeRequestRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Failures are not retried by Messenger, as the exceptions are caught: the
 * request is marked as failed and the user can retry from the quarantine.
 */
#[AsMessageHandler]
final class SendOneClickUnsubscribeHandler
{
    private const HTTP_TIMEOUT = 5;

    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private UnsubscribeRequestRepository $unsubscribeRequestRepository,
        private LoggerInterface $logger,
    ) {
        // The one-click URL comes from an untrusted email: it must never allow
        // to reach the internal network (e.g. the other AgentJ containers).
        $this->httpClient = new NoPrivateNetworkHttpClient($httpClient);
    }

    public function __invoke(SendOneClickUnsubscribe $message): void
    {
        $unsubscribeRequest = $this->unsubscribeRequestRepository->find($message->unsubscribeRequestId);

        if ($unsubscribeRequest === null || !$unsubscribeRequest->isPending()) {
            return;
        }

        if ($this->sendRequest($message->url)) {
            $unsubscribeRequest->markAsSent();
        } else {
            $unsubscribeRequest->markAsFailed();
        }

        $this->unsubscribeRequestRepository->save($unsubscribeRequest);
    }

    /**
     * Send the one-click request and return whether the list server accepted
     * it.
     */
    private function sendRequest(string $url): bool
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => 'List-Unsubscribe=One-Click',
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => 'AgentJ',
                ],
                'max_redirects' => 0,
                'timeout' => self::HTTP_TIMEOUT,
                'max_duration' => self::HTTP_TIMEOUT * 2,
            ]);

            $statusCode = $response->getStatusCode();
        } catch (HttpClientException $exception) {
            $this->logger->warning('One-click unsubscription to {url} failed: {error}', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        // A redirection is not followed (it could lead anywhere), but the list
        // server usually redirects to a confirmation page once the request is
        // processed: it is considered as a success.
        if ($statusCode < 200 || $statusCode >= 400) {
            $this->logger->warning('One-click unsubscription to {url} failed with HTTP code {code}', [
                'url' => $url,
                'code' => $statusCode,
            ]);

            return false;
        }

        return true;
    }
}
