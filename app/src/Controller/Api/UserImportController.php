<?php

namespace App\Controller\Api;

use App\Security\ApiKeyUser;
use App\Service\UserApiImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api')]
class UserImportController extends AbstractController
{
    public function __construct(private UserApiImportService $userApiImportService)
    {
    }

    /**
     * Import/update users for the calling domain.
     *
     * Auth: header "X-Api-Key: <key>" (see agentj:api-key:generate).
     *
     * Body: JSON array of objects, e.g.
     * [{"email": "jane@example.com", "firstName": "Jane", "lastName": "Doe", "group": "Sales"}]
     * Only "email" is required. "group" is created if it doesn't exist yet.
     */
    #[Route(path: '/users/import', name: 'api_users_import', methods: 'POST')]
    public function import(Request $request): JsonResponse
    {
        $apiUser = $this->getUser();
        if (!$apiUser instanceof ApiKeyUser) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body, expected an array of user objects'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (count($payload) === 0) {
            return new JsonResponse(['error' => 'Empty payload'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->userApiImportService->importUsers($apiUser->getDomain(), $payload);

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
