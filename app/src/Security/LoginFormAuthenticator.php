<?php

namespace App\Security;

use App\Entity\ImapConnector;
use App\Entity\User;
use App\Service\LdapService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private LdapService $ldapService,
        private LoggerInterface $logger
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->getString('username', '');
        $csrfToken = $request->request->getString('_csrf_token', '');
        $password = $request->request->getString('password', '');

        $token = new CsrfToken('authenticate', $csrfToken);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        // check if a local user exists
        $localUser = $this->getLocalUser($username);
        if ($localUser) {
            return new Passport(
                new UserBadge($username),
                new PasswordCredentials($password),
                [
                    new CsrfTokenBadge('authenticate', $csrfToken),
                    new RememberMeBadge(),
                ]
            );
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => strtolower($username),
        ]);
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'imapLogin' => strtolower($username),
            ]);
        }

        if (!$user) {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('Generics.messages.incorrectCredential')
            );
        }

        $ldapBind = $this->ldapService->bindUser($user, $password);
        if ($ldapBind) {
            return new SelfValidatingPassport(new UserBadge($username), [new RememberMeBadge()]);
        }

        $loginImap = $this->getLoginImap($user, $password);

        if (!$loginImap) {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('Generics.messages.incorrectCredential')
            );
        }

        return new SelfValidatingPassport(new UserBadge($user->getEmail()), [new RememberMeBadge()]);
    }

    private function getLoginImap(User $user, string $password): bool
    {
        $cm = new ClientManager($options = []);
        $login = $user->getImapLogin() ? $user->getImapLogin() : $user->getEmail();
        foreach ($user->getDomain()->getConnectors() as $connector) {
            if ($connector instanceof ImapConnector) {
                $client = $cm->make([
                    'host' => $connector->getImapHost(),
                    'port' => $connector->getImapPort(),
                    'encryption' => $connector->getImapProtocol(),
                    'validate_cert' => !$connector->isImapNoValidateCert(),
                    'username' => $login,
                    'password' => $password,
                    'protocol' => 'imap'
                ]);

                try {
                    $client->connect();
                    if ($client->isConnected()) {
                        return true;
                    }
                } catch (ConnectionFailedException $exc) {
                    $this->logger->error(
                        'IMAP connection failed for user "{imap_login}" on host "{imap_host}:{imap_port}": {message}',
                        [
                            'exception' => $exc,
                            'message' => $exc->getMessage(),
                            'user_id' => $user->getId(),
                            'imap_login' => $login,
                            'imap_host' => $connector->getImapHost(),
                            'imap_port' => $connector->getImapPort(),
                            'imap_protocol' => $connector->getImapProtocol(),
                        ]
                    );
                } catch (ImapServerErrorException $exc) {
                    $this->logger->error(
                        'IMAP server error for user "{imap_login}" on host "{imap_host}:{imap_port}": {message}',
                        [
                            'exception' => $exc,
                            'message' => $exc->getMessage(),
                            'user_id' => $user->getId(),
                            'imap_login' => $login,
                            'imap_host' => $connector->getImapHost(),
                            'imap_port' => $connector->getImapPort(),
                            'imap_protocol' => $connector->getImapProtocol(),
                        ]
                    );
                }
            }
        }
        return false;
    }

    private function getLocalUser(string $userName): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => strtolower($userName)]);
        if ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            return $user;
        }
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        if ($user->getDomain() && $user->getDomain()->getDefaultLang()) {
            $request->getSession()->set('_locale', $user->getDomain()->getDefaultLang());
        }


        if ($user->getPreferedLang()) {
            $request->getSession()->set('_locale', $user->getPreferedLang());
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->urlGenerator->generate('message'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
