<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ApiKeyUser;
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
}
