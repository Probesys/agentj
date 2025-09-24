<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use Psr\Log\LoggerInterface;
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
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return (
            $request->attributes->get('_route') === 'connect_azure_check' ||
            $request->attributes->get('_route') === 'connect_oauth_check'
        );
    }

    public function authenticate(Request $request): Passport
    {
        if ($request->attributes->get('_route') === 'connect_azure_check') {
            return $this->authenticateAzure($request);
        } else {
            return $this->authenticateOAuth($request);
        }
    }

    /**
     * Authenticate the user via a generic OAuth2 client.
     */
    private function authenticateOAuth(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('generic');
        $session = $request->getSession();

        try {
            $accessToken = $this->fetchAccessToken($client);
        } catch (\Exception $e) {
            $this->logger->error('OAuth2 fetch access token failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);
            $this->setAuthenticationError($session, new TranslatableMessage('Generics.messages.oauthInvalidSetup'));

            throw new CustomUserMessageAuthenticationException(
                new TranslatableMessage('Generics.messages.oauthInvalidSetup')
            );
        }

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $session) {
                try {
                    $oauthUser = $client->fetchUserFromToken($accessToken);
                } catch (\Exception $e) {
                    $this->logger->error('OAuth2 fetch user from token failed: {message}', [
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ]);
                    $this->setAuthenticationError(
                        $session,
                        new TranslatableMessage('Generics.messages.oauthInvalidSetup')
                    );

                    return null;
                }

                $oauthUserAttributes = $oauthUser->toArray();

                if (!isset($oauthUserAttributes['email'])) {
                    $this->logger->error('OAuth2 login failed: "email" is missing in userinfo');
                    $this->setAuthenticationError(
                        $session,
                        new TranslatableMessage('Generics.messages.incorrectCredential')
                    );

                    return null;
                }

                $email = $oauthUserAttributes['email'];
                $user = $this->userRepository->findOneByEmail($email);

                if ($user === null) {
                    $this->logger->error("OAuth2 login failed: user with email {$email} does not exist");
                    $this->setAuthenticationError(
                        $session,
                        new TranslatableMessage('Generics.messages.incorrectCredential')
                    );

                    return null;
                }

                return $user;
            })
        );
    }

    /**
     * Authenticate the user via Azure (deprecated, use OAuth2 instead).
     */
    private function authenticateAzure(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('azure');
        try {
            $accessToken = $this->fetchAccessToken($client);
        } catch (\Exception $e) {
            $this->logger->error('Azure OAuth2 fetch access token failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            throw new CustomUserMessageAuthenticationException(
                new TranslatableMessage('Generics.messages.azurOAuthInvalidIdentity'),
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

    private function setAuthenticationError(SessionInterface $session, TranslatableMessage $error): void
    {
        $session->set(
            SecurityRequestAttributes::AUTHENTICATION_ERROR,
            new CustomUserMessageAuthenticationException($this->translator->trans($error)),
        );
    }
}
