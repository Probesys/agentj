<?php

namespace App\Controller\Api;

use App\Entity\Domain;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\MessageRecipientRepository;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
use App\Service\MessageService;
use App\Service\PendingMessageApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private PendingMessageApiService $pendingMessageApiService,
        private UserRepository $userRepository,
        private MessageRecipientRepository $messageRecipientRepository,
        private MessageService $messageService,
    ) {
    }

    /**
     * List messages waiting to be processed (untreated, plus spam under the
     * domain's report level) for the calling domain, so it can be polled
     * instead of relying on the agentj:send-report-mail notification email.
     *
     * Auth: header "X-Api-Key: <key>" (see agentj:api-key:generate).
     *
     * Optional query params:
     *  - email: restrict to a single user of the domain
     *  - since: unix timestamp, only messages received after this date
     */
    #[Route(path: '/messages/pending', name: 'api_messages_pending', methods: 'GET')]
    public function pending(Request $request): JsonResponse
    {
        $apiUser = $this->getUser();
        if (!$apiUser instanceof ApiKeyUser) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $domain = $apiUser->getDomain();

        // Domain::$users is a ManyToMany (mappedBy: 'domains') that is never
        // populated in practice — the real, always-populated relation is
        // User::$domain (ManyToOne), same one used by SendReportMailCommand.
        $domainUsers = $this->userRepository->findBy(['domain' => $domain]);

        $email = $request->query->get('email');
        if ($email !== null) {
            $user = null;
            foreach ($domainUsers as $candidate) {
                if ($candidate->getEmail() === $email && $candidate->getOriginalUser() === null) {
                    $user = $candidate;
                    break;
                }
            }

            if ($user === null) {
                return new JsonResponse(['error' => "Unknown user '$email' for this domain"], Response::HTTP_NOT_FOUND);
            }

            $users = [$user];
        } else {
            $users = array_values(array_filter(
                $domainUsers,
                fn (User $user) => in_array('ROLE_USER', $user->getRoles(), true) && $user->getOriginalUser() === null,
            ));
        }

        $sinceDate = $request->query->get('since');
        $sinceDate = $sinceDate !== null ? (int) $sinceDate : null;

        $result = $this->pendingMessageApiService->getPendingMessagesForUsers($users, $sinceDate);

        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * Return the sanitized content of a quarantined message, so a domain can
     * display a preview without going through the admin portal's session
     * auth. Same sanitized HTML rendering as the admin preview modal
     * (see MessageController::showIframeDetailMsgs).
     *
     * Auth: header "X-Api-Key: <key>" (see agentj:api-key:generate).
     *
     * recipientId/partitionTag/mailId are the same identifiers already
     * returned by GET /api/messages/pending.
     */
    #[Route(
        path: '/messages/{recipientId}/{partitionTag}/{mailId}/content',
        name: 'api_messages_content',
        methods: 'GET',
    )]
    public function content(int $recipientId, int $partitionTag, string $mailId): JsonResponse
    {
        $apiUser = $this->getUser();
        if (!$apiUser instanceof ApiKeyUser) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $messageRecipient = $this->messageRecipientRepository->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'address' => $recipientId,
        ]);

        if ($messageRecipient === null || !$this->belongsToDomain($messageRecipient, $apiUser->getDomain())) {
            return new JsonResponse(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        $content = $this->messageService->extractQuarantineContent($messageRecipient->getMessage());

        if ($content === null) {
            return new JsonResponse(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'sender' => $content['sender'],
            'subject' => $content['subject'],
            'date' => $messageRecipient->getMessage()->getTimeIso(),
            'html' => $content['htmlBody'],
            'text' => $content['textBody'],
        ], Response::HTTP_OK);
    }

    /**
     * Make sure the message's recipient address belongs to the given domain,
     * so a domain's API key can never be used to read a message addressed to
     * another domain.
     *
     * Compares by the address' own domain (like
     * MessageService::authorizeSenderForDomain/banSenderForDomain), not via
     * a User lookup: many quarantined recipients (e.g. plus-tagged
     * addresses) have no matching row in `users` at all, so a User-based
     * check would wrongly 404 legitimate, in-domain messages.
     */
    private function belongsToDomain(MessageRecipient $messageRecipient, Domain $domain): bool
    {
        $address = $messageRecipient->getAddress();

        if ($address === null) {
            return false;
        }

        return strtolower($address->getReverseDomain()) === strtolower((string) $domain->getDomain());
    }
}
