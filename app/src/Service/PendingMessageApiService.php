<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\Domain;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\MessageRecipientSearchRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the JSON payload used by the /api/messages/pending endpoint.
 * Mirrors what SendReportMailCommand puts in the report email (same
 * "untreated" + "under the domain's report spam level" message set), so a
 * domain can poll for messages to process instead of receiving an email.
 */
class PendingMessageApiService
{
    public function __construct(
        private MessageRecipientSearchRepository $messageRecipientSearchRepository,
        private MessageService $messageService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param User[] $users
     * @return array<int, array{
     *     email: string,
     *     counts: array{untreated: int, spammed: int},
     *     messages: array<int, mixed>,
     * }>
     */
    public function getPendingMessagesForUsers(array $users, ?int $sinceDate = null): array
    {
        $result = [];

        foreach ($users as $user) {
            $messageRecipients = $this->getPendingMessageRecipients($user, $sinceDate);

            if (count($messageRecipients) === 0) {
                continue;
            }

            $result[] = [
                'email' => $user->getEmail(),
                'counts' => [
                    'untreated' => $this->messageRecipientSearchRepository->countByType(
                        $user,
                        MessageStatus::UNTREATED,
                    ),
                    'spammed' => $this->messageRecipientSearchRepository->countByType(
                        $user,
                        MessageStatus::SPAMMED,
                    ),
                ],
                'messages' => array_map(
                    fn (MessageRecipient $messageRecipient) => $this->formatMessageRecipient(
                        $user,
                        $messageRecipient,
                    ),
                    $messageRecipients,
                ),
            ];
        }

        return $result;
    }

    /**
     * @return MessageRecipient[]
     */
    private function getPendingMessageRecipients(User $user, ?int $sinceDate): array
    {
        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $sinceDate,
        )->getResult();

        $domain = $user->getDomain();
        $reportSpamLevel = $domain instanceof Domain ? $domain->getReportSpamLevel() : null;

        if ($reportSpamLevel !== null && $reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                maxSpamLevel: $reportSpamLevel,
                fromDate: $sinceDate,
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessageRecipient(User $user, MessageRecipient $messageRecipient): array
    {
        $message = $messageRecipient->getMessage();
        $token = $this->messageService->getReleaseToken($message, $user);

        $routeParams = [
            'token' => $token,
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'mailId' => $messageRecipient->getMailId(),
            'recipientId' => $messageRecipient->getAddress()->getId(),
        ];

        return [
            'mailId' => $messageRecipient->getMailId(),
            'partitionTag' => $messageRecipient->getPartitionTag(),
            'recipientId' => $messageRecipient->getAddress()->getId(),
            'sender' => $message->getFromAddr(),
            'subject' => $message->getSubject(),
            'date' => $message->getTimeIso(),
            'status' => MessageStatus::getStatusName($messageRecipient->getStatus()),
            'spamLevel' => $messageRecipient->getBspamLevel(),
            'authorizeUrl' => $this->urlGenerator->generate(
                'portal_message_authorized',
                $routeParams,
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'restoreUrl' => $this->urlGenerator->generate(
                'portal_message_restore',
                $routeParams,
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];
    }
}
