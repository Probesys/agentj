<?php

namespace App\Controller;

use App\Service\AltchaService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AltchaController extends AbstractController
{
    public function __construct(
        private AltchaService $altchaService,
    ) {
    }

    #[Route('/altcha', name: 'altcha_challenge')]
    public function show(): JsonResponse
    {
        try {
            $challenge = $this->altchaService->buildChallenge();
        } catch (Exception $ex) {
            return new JsonResponse(
                $ex,
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
        return new JsonResponse($challenge->toArray());
    }
}
