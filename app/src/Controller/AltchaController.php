<?php

namespace App\Controller;

use App\Service\AltchaService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AltchaController extends AbstractController
{
    public function __construct(
        private AltchaService $altchaService,
        #[Autowire(env: 'int:ALTCHA_COST')]
        private int $cost,
    ) {
    }

    #[Route('/altcha', name: 'altcha_challenge')]
    public function show(): JsonResponse
    {
        try {
            $challenge = $this->altchaService->buildChallenge($this->cost);
        } catch (Exception $ex) {
            return new JsonResponse(
                $ex,
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
        return new JsonResponse($challenge->toArray());
    }
}
