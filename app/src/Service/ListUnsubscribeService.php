<?php

namespace App\Service;

use App\Amavis\MessageStatus;
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
use App\Util\Email;
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

    public function canUnsubscribe(MessageRecipient $messageRecipient, User $user): bool
    {
        return $this->isAllowed($messageRecipient, $user)
            && $this->getUnsubscribeMethods($messageRecipient) !== null;
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
     * returned instead of sending a new one.
     *
     * $requestedBy is the user who actually makes the request, if different
     * from $user (e.g. an admin impersonating $user).
     *
     * @throws CannotUnsubscribeException
     */
    public function unsubscribe(
        MessageRecipient $messageRecipient,
        User $user,
        ?User $requestedBy = null,
    ): UnsubscribeResult {
        if (!$this->isAllowed($messageRecipient, $user)) {
            throw new CannotUnsubscribeException('The user cannot unsubscribe from the list of this message.');
        }

        $metadata = $this->getMetadata($messageRecipient);
        $unsubscribeMethods = $this->parseMetadata($metadata);
        if ($metadata === null || $unsubscribeMethods === null) {
            throw new CannotUnsubscribeException('The message cannot be unsubscribed from.');
        }

        $lastRequest = $this->findLastRequest($messageRecipient, $metadata);
        if ($lastRequest !== null && $lastRequest->isPending()) {
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
            $requestedBy ?? $user,
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

    /**
     * Only the recipient (or the owner of the recipient alias) can unsubscribe,
     * and only from the mailing list messages which are not spam nor virus.
     */
    private function isAllowed(MessageRecipient $messageRecipient, User $user): bool
    {
        if (!$messageRecipient->getMessage()->getIsMlist()) {
            return false;
        }

        if (in_array($messageRecipient->getStatus(), [MessageStatus::SPAMMED, MessageStatus::VIRUS], true)) {
            return false;
        }

        return $this->isRecipientOf($user, $messageRecipient);
    }

    private function isRecipientOf(User $user, MessageRecipient $messageRecipient): bool
    {
        $recipientEmail = $messageRecipient->getAddress()?->getEmail();
        if ($recipientEmail === null) {
            return false;
        }

        $userEmails = [$user->getEmail()];
        foreach ($user->getAliases() ?? [] as $alias) {
            $userEmails[] = $alias->getEmail();
        }

        $recipientEmail = Email::normalize($recipientEmail);

        foreach ($userEmails as $userEmail) {
            if ($userEmail !== null && Email::normalize($userEmail) === $recipientEmail) {
                return true;
            }
        }

        return false;
    }
}
