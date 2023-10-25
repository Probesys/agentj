<?php

namespace App\Security;
//use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;


use App\Entity\User;
use App\Service\LdapService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;


class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {

    use TargetPathTrait;

    private $urlGenerator;
    private $params;
    private $csrfTokenManager;
    private $entityManager;
    private $translator;
    private LdapService $ldapService;
    private $logger;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
            UrlGeneratorInterface $urlGenerator,
            ParameterBagInterface $params,
            CsrfTokenManagerInterface $csrfTokenManager,
            EntityManagerInterface $entityManager,
            TranslatorInterface $translator, 
            LdapService $ldapService,
            LoggerInterface $logger

    ) {
        $this->urlGenerator = $urlGenerator;
        $this->params = $params;
        $this->logger = $logger;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->ldapService = $ldapService;
    }

    public function authenticate(Request $request): Passport {

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

/*@var $user User */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($username)]);
        if (!$user){
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['imapLogin' => strtolower($username)]);
        }

        if (!$user) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('Generics.messages.incorrectCredential'));
        }

        $ldapBind = $this->ldapService->bindUser($user, $password);
        if ($ldapBind){
            return new SelfValidatingPassport(new UserBadge($username), [new RememberMeBadge()]);
        }
        
//        dd($ldapBind);
        
        $loginImap = $this->getLoginImap($user, $password);
        
        if (!$loginImap) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('Generics.messages.incorrectCredential'));
        }
//        dd($username);
        return new SelfValidatingPassport(new UserBadge($user->getEmailFromRessource()), [new RememberMeBadge()]);
    }



    private function getLoginImap(User $user, String $password) {
        $cm = new ClientManager($options = []);
        $login = $user->getImapLogin() ? $user->getImapLogin() : $user->getEmailFromRessource();
        $client = $cm->make([
            'host'          => $user->getDomain()->getSrvImap(),
            'port'          =>  $user->getDomain()->getImapPort(),
            'encryption'    => $user->getDomain()->getImapFlag(),
            'validate_cert' => !$user->getDomain()->getImapNoValidateCert(),
            'username'      => $login,
            'password'      => $password,
            'protocol'      => 'imap'
        ]);

        try {
            $client->connect();
            return $client->isConnected();
        } catch (ConnectionFailedException $exc) {
            $this->logger->error("User cannot connect \t (Error " . $exc->getCode() . ")\t" . $exc->getMessage());
            return false;
        }

        
        
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
        
        if ($token->getUser()->getDomain() && $token->getUser()->getDomain()->getDefaultLang()){
            $request->getSession()->set('_locale',  $token->getUser()->getDomain()->getDefaultLang());
        }
        
        
        if ($token->getUser()->getPreferedLang()){
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
