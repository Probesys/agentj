<?php

namespace App\Security;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticates requests to /api/* using a per-domain API key sent in the
 * "X-Api-Key" header. Keys are generated via `agentj:api-key:generate` and
 * stored hashed (Domain::$apiKeyHash) — the plaintext key is never persisted.
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private DomainRepository $domainRepository)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Api-Key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = (string) $request->headers->get('X-Api-Key', '');
        if ($apiKey === '') {
            throw new CustomUserMessageAuthenticationException('No API key provided.');
        }

        $hash = hash('sha256', $apiKey);
        $domain = $this->domainRepository->findOneBy(['apiKeyHash' => $hash]);

        if (!$domain instanceof Domain) {
            throw new CustomUserMessageAuthenticationException('Invalid API key.');
        }

        return new SelfValidatingPassport(
            new UserBadge($domain->getDomain(), fn () => new ApiKeyUser($domain)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }
}
