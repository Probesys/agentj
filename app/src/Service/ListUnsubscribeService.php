<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\MessageListMetadata;
use App\Entity\MessageRecipient;
use App\Entity\UnsubscribeRequest;
use App\Exception\CannotUnsubscribeException;
use App\Message\SendOneClickUnsubscribe;
use App\Model\UnsubscribeMethods;
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

    /**
     * Return the unsubscribe states of the message recipients which can be
     * unsubscribed from, indexed by "partitionTag-mailId-rseqnum".
     *
     * @param MessageRecipient[] $messageRecipients
     *
     * @return array<string, array{methods: UnsubscribeMethods, lastRequest: ?UnsubscribeRequest}>
     */
    public function getUnsubscribeStates(array $messageRecipients): array
    {
        if ($messageRecipients === []) {
            return [];
        }

        $mailIds = array_map(
            fn (MessageRecipient $recipient): string => $recipient->getMailId(),
            $messageRecipients
        );
        $metadataList = $this->messageListMetadataRepository->findBy(['mailId' => $mailIds]);
        $metadataByMessage = [];
        foreach ($metadataList as $metadata) {
            $metadataByMessage[$metadata->getPartitionTag() . '-' . $metadata->getMailId()] = $metadata;
        }

        $addresses = array_filter(array_map(
            fn (MessageRecipient $recipient): ?Address => $recipient->getAddress(),
            $messageRecipients,
        ));

        $requests = $this->unsubscribeRequestRepository->findBy(
            ['address' => $addresses],
            ['createdAt' => 'DESC', 'id' => 'DESC'],
        );

        $lastRequestsByList = [];
        $lastRequestsByMessageRecipient = [];
        foreach ($requests as $request) {
            if ($request->getListId() !== null) {
                $lastRequestsByList[$request->getAddress()->getId() . '-' . $request->getListId()] ??= $request;
            }
            $key = $request->getPartitionTag() . '-' . $request->getMailId() . '-' . $request->getRseqnum();
            $lastRequestsByMessageRecipient[$key] ??= $request;
        }

        $states = [];
        foreach ($messageRecipients as $messageRecipient) {
            $metadata = $metadataByMessage[$messageRecipient->getPartitionTag() . '-' . $messageRecipient->getMailId()]
                ?? null;
            $unsubscribeMethods = $this->parseMetadata($metadata);
            if ($metadata === null || $unsubscribeMethods === null) {
                continue;
            }

            $key = $messageRecipient->getPartitionTag()
                . '-' . $messageRecipient->getMailId()
                . '-' . $messageRecipient->getRseqnum();

            $listId = $metadata->getListId();
            $address = $messageRecipient->getAddress();

            if ($listId !== null && $address !== null) {
                $lastRequest = $lastRequestsByList[$address->getId() . '-' . $listId] ?? null;
            } else {
                $lastRequest = $lastRequestsByMessageRecipient[$key] ?? null;
            }

            $states[$key] = ['methods' => $unsubscribeMethods, 'lastRequest' => $lastRequest];
        }

        return $states;
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
     * With the HTTPS and mailto methods, the user completes the
     * unsubscription on the list page, opened by the browser.
     *
     * The access must have been checked before (see UnsubscribeVoter).
     *
     * @throws CannotUnsubscribeException
     */
    public function unsubscribe(MessageRecipient $messageRecipient): UnsubscribeRequest
    {
        $metadata = $this->getMetadata($messageRecipient);
        $unsubscribeMethods = $this->parseMetadata($metadata);
        if ($metadata === null || $unsubscribeMethods === null) {
            throw new CannotUnsubscribeException('The message cannot be unsubscribed from.');
        }

        $lastRequest = $this->findLastRequest($messageRecipient, $metadata);
        if ($lastRequest !== null && $lastRequest->isStillPending()) {
            return $lastRequest;
        }

        $method = (string) $unsubscribeMethods->getPreferredMethod();
        $status = $method === UnsubscribeMethods::METHOD_ONE_CLICK
            ? UnsubscribeRequest::STATUS_PENDING
            : UnsubscribeRequest::STATUS_INITIATED;

        $request = new UnsubscribeRequest(
            $messageRecipient,
            $metadata->getListId(),
            $method,
            $status,
        );
        $this->unsubscribeRequestRepository->save($request);

        if ($method === UnsubscribeMethods::METHOD_ONE_CLICK) {
            $this->bus->dispatch(new SendOneClickUnsubscribe(
                (int) $request->getId(),
                (string) $unsubscribeMethods->httpsUrl,
            ));
        }

        return $request;
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
