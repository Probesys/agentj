<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Translation\TranslatableMessage;

class OAuthAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_azure_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('azure');
        try {
            $accessToken = $this->fetchAccessToken($client);
        } catch (IdentityProviderAuthenticationException $ex) {
            throw new CustomUserMessageAuthenticationException(
                new TranslatableMessage('Generics.messages.azurOAuthInvalidIdentity'),
                []
            );
        } catch (InvalidStateException $ex) {
            throw new CustomUserMessageAuthenticationException(
                new TranslatableMessage('Generics.messages.azurOAuthInvalidIdentity'),
                []
            );
        }

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                $oAuthUser = $client->fetchUserFromToken($accessToken);
                $existingUser = $this->userRepository->findOneByUid($oAuthUser->getId());

                if ($existingUser !== null) {
                    return $existingUser;
                }

                $existingUser = $this->userRepository->findOneByPrincipalName($oAuthUser->toArray()['unique_name']);

                if ($existingUser !== null) {
                    $existingUser->setUid($oAuthUser->getId());
                    $this->entityManager->flush();

                    return $existingUser;
                }

                throw new CustomUserMessageAuthenticationException(
                    new TranslatableMessage('Generics.messages.incorrectCredential')
                );
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $redirectUri = $this->getTargetPath($request->getSession(), $firewallName) ??
                $this->router->generate('homepage');

        return new RedirectResponse($redirectUri);
    }

    /**
     * @throws \Throwable
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw $exception->getPrevious() ?? $exception;
    }
}
