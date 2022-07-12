<?php

namespace App\Security;

use App\Entity\Domain;
use App\Entity\User;
use App\Service\Office365Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webklex\PHPIMAP\ClientManager;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {

    use TargetPathTrait;

    private $urlGenerator;
    private $params;
    private $csrfTokenManager;
    private $entityManager;
    private $translator;
    private $office365Service;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
            UrlGeneratorInterface $urlGenerator,
            ParameterBagInterface $params,
            CsrfTokenManagerInterface $csrfTokenManager,
            EntityManagerInterface $entityManager,
            TranslatorInterface $translator,
            Office365Service $office365Service
            
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->params = $params;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->office365Service = $office365Service;
    }


    
    public function authenticate(Request $request): PassportInterface {

        $username = $request->request->get('username', '');
        $csrf_token = $request->request->get('_csrf_token', '');
        $password = $request->request->get('password', '');

        $token = new CsrfToken('authenticate', $csrf_token);
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
                new CsrfTokenBadge('authenticate', $csrf_token),
                new RememberMeBadge(),
                    ]
            );
        }

        // check if a domain exists and if it's active
        $domain = $this->getUserDomainAuth($username);
        if (!$domain) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('Generics.messages.incorrectCredential'));
        }
//        dd($domain);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($username)]);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('Generics.messages.incorrectCredential'));
        }
/* @var $user User */
        if ($user->getDomain()->getAuthMode() == 'azurAD'){
            return new SelfValidatingPassport(new UserBadge($username), [new RememberMeBadge()]);
        }

//        $tmp = $this->getLoginOAuthImap($user, $password);
        $loginImap = $this->getLoginImap($user, $password);
        if (!$loginImap) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('Generics.messages.incorrectCredential'));
        }
        return new SelfValidatingPassport(new UserBadge($username), [new RememberMeBadge()]);
    }

    private function getUserDomainAuth(String $username) {
        $mail = explode('@', $username);
        if (!isset($mail[1])) {
            return false;
        }
        /* @var $domain Domain */
        $domain = $this->entityManager->getRepository(Domain::class)->findOneBy(['domain' => strtolower($mail[1]), 'active' => 1]);
        if (!$domain) {
            return false;
        }

        return $domain;
    }

    private function getLoginImap(User $user, String $password) {
        $domain = $user->getDomain();
        $conStr = $domain->getSrvImap() . ":" . $domain->getImapPort() . $domain->getImapFlag();
        if ($domain->getImapNoValidateCert()) {
            $conStr .= '/novalidate-cert';
        }

        $conStr = '{' . $conStr . '}';
        $mbox = @imap_open($conStr, $user->getEmailFromRessource(), $password, 0, 1);
        if (!imap_errors() && $mbox) {
            return true;
        }
        return false;
    }

    private function getLoginOAuthImap(User $user, String $password) {
//        dd($user);
//return new RedirectResponse($this->urlGenerator->generate('app_login',['step' => 2]));
/*@var $user User */

        $token = $this->office365Service->getAccestoken();
        $cm = new ClientManager();
        $client = $cm->make([
            'host' => 'outlook.office365.com',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'protocol' => 'imap',
            'username' => 'ctresvaux@5vrfgv.onmicrosoft.com',
            'password' => $token,
            'authentication' => "oauth",
        ]);
        $client->connect();
dd($client);
        $domain = $user->getDomain();
        $conStr = $domain->getSrvImap() . ":" . $domain->getImapPort() . $domain->getImapFlag();
        if ($domain->getImapNoValidateCert()) {
            $conStr .= '/novalidate-cert';
        }

        $conStr = '{' . $conStr . '}';
        $mbox = @imap_open($conStr, $user->getEmailFromRessource(), $password, 0, 1);
        if (!imap_errors() && $mbox) {
            return true;
        }
        return false;
    }

    private function getLocalUser(String $userName) {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => strtolower($userName)]);
        if ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            return $user;
        }
        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response {

        $request->getSession()->set('originalUser', $token->getUser()->getUsername());
        
//$oAuthconfig = $token->getUser()->getDomain()->getOAuthConfig();
//$tenantId = $oAuthconfig->getTenantId();
//$clientId = $oAuthconfig->getClientId();
//$secretId = $oAuthconfig->getClientSecret();
//$url = "https://login.microsoftonline.com/" . $tenantId . '/oauth2/v2.0/authorize?';
//$url .= "client_id=" . $clientId ;
//$url .= "response_mode=fragment&scope=openid%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fuser.read";
////dd($url);
//return new RedirectResponse($url);


        if ($token->getUser()->getDomain() && $token->getUser()->getDomain()->getDefaultLang()) {
            $request->getSession()->set('_locale', $token->getUser()->getDomain()->getDefaultLang());
        }


        if ($token->getUser()->getPreferedLang()) {
            $request->getSession()->set('_locale', $token->getUser()->getPreferedLang());
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->urlGenerator->generate('message'));
    }

    protected function getLoginUrl(Request $request): string {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

}
