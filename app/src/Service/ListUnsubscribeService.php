<?php

namespace App\Service;

use App\Entity\MessageListMetadata;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Entity\User;
use App\Exception\CannotUnsubscribeException;
use App\Message\SendOneClickUnsubscribe;
use App\Model\UnsubscribeMethods;
use App\Model\UnsubscribeResult;
use App\Repository\MessageListMetadataRepository;
use App\Repository\UnsubscribeRequestRepository;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unsubscribe the recipient of a mailing list message from the list, thanks
 * to its List-Unsubscribe headers (RFC 2369 and RFC 8058).
 */
class ListUnsubscribeService
{
    public function __construct(
        private ListUnsubscribeParser $listUnsubscribeParser,
        private MessageListMetadataRepository $messageListMetadataRepository,
        private UnsubscribeRequestRepository $unsubscribeRequestRepository,
        private MessageBusInterface $bus,
    ) {
    }

    public function getUnsubscribeMethods(MessageRecipient $messageRecipient): ?UnsubscribeMethods
    {
        return $this->parseMetadata($this->getMetadata($messageRecipient));
    }

    public function getLastRequest(MessageRecipient $messageRecipient): ?UnsubscribeRequest
    {
        return $this->findLastRequest($messageRecipient, $this->getMetadata($messageRecipient));
    }

    /**
     * Unsubscribe the recipient of the message from the list, with the
     * preferred method.
     *
     * If a one-click request is already being sent for the list, it is
     * returned instead of sending a new one (see
     * UnsubscribeRequest::isStillPending()).
     *
     * The access must have been checked before (see UnsubscribeVoter).
     * $requestedBy is the user who actually makes the request: the recipient,
     * or the admin impersonating them.
     *
     * @throws CannotUnsubscribeException
     */
    public function unsubscribe(
        MessageRecipient $messageRecipient,
        User $requestedBy,
    ): UnsubscribeResult {
        $metadata = $this->getMetadata($messageRecipient);
        $unsubscribeMethods = $this->parseMetadata($metadata);
        if ($metadata === null || $unsubscribeMethods === null) {
            throw new CannotUnsubscribeException('The message cannot be unsubscribed from.');
        }

        $lastRequest = $this->findLastRequest($messageRecipient, $metadata);
        if ($lastRequest !== null && $lastRequest->isStillPending()) {
            return new UnsubscribeResult($lastRequest, null);
        }

        $method = (string) $unsubscribeMethods->getPreferredMethod();
        $redirectUrl = null;

        if ($method === UnsubscribeMethods::METHOD_ONE_CLICK) {
            $status = UnsubscribeRequest::STATUS_PENDING;
        } elseif ($method === UnsubscribeMethods::METHOD_HTTPS) {
            $status = UnsubscribeRequest::STATUS_INITIATED;
            $redirectUrl = $unsubscribeMethods->httpsUrl;
        } else {
            $status = UnsubscribeRequest::STATUS_INITIATED;
            $redirectUrl = $unsubscribeMethods->mailto;
        }

        $request = new UnsubscribeRequest(
            $messageRecipient,
            $metadata->getListId(),
            $method,
            $status,
            $requestedBy,
        );
        $this->unsubscribeRequestRepository->save($request);

        if ($method === UnsubscribeMethods::METHOD_ONE_CLICK) {
            $this->bus->dispatch(new SendOneClickUnsubscribe(
                (int) $request->getId(),
                (string) $unsubscribeMethods->httpsUrl,
            ));
        }

        return new UnsubscribeResult($request, $redirectUrl);
    }

    /**
     * The metadata are loaded explicitly, as Message has no relation to them
     * (see MessageListMetadata::$message).
     */
    public function getMetadata(MessageRecipient $messageRecipient): ?MessageListMetadata
    {
        return $this->messageListMetadataRepository->find([
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
        ]);
    }

    private function parseMetadata(?MessageListMetadata $metadata): ?UnsubscribeMethods
    {
        if ($metadata === null) {
            return null;
        }

        $unsubscribeMethods = $this->listUnsubscribeParser->parse(
            $metadata->getListUnsubscribe(),
            $metadata->getListUnsubscribePost(),
        );

        return $unsubscribeMethods->hasMethod() ? $unsubscribeMethods : null;
    }

    private function findLastRequest(
        MessageRecipient $messageRecipient,
        ?MessageListMetadata $metadata,
    ): ?UnsubscribeRequest {
        $listId = $metadata?->getListId();
        $address = $messageRecipient->getAddress();

        // Without List-Id, the messages of a list cannot be related together
        if ($listId !== null && $address !== null) {
            return $this->unsubscribeRequestRepository->findLastForList($address, $listId);
        }

        return $this->unsubscribeRequestRepository->findLastForMessageRecipient($messageRecipient);
    }
}
