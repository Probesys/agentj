<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator {

  use TargetPathTrait;

  private $entityManager;
  private $router;
  private $csrfTokenManager;
  private $passwordEncoder;

  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder) {
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->csrfTokenManager = $csrfTokenManager;
    $this->passwordEncoder = $passwordEncoder;
  }

  public function supports(Request $request) {
    return 'app_login' === $request->attributes->get('_route') && $request->isMethod('POST');
  }

  public function getCredentials(Request $request) {
    $credentials = [
        'username' => $request->request->get('username'),
        'password' => $request->request->get('password'),
        'csrf_token' => $request->request->get('_csrf_token'),
    ];
    $request->getSession()->set(
            Security::LAST_USERNAME, $credentials['username']
    );

    return $credentials;
  }

  public function getUser($credentials, UserProviderInterface $userProvider) {
    $token = new CsrfToken('authenticate', $credentials['csrf_token']);
    if (!$this->csrfTokenManager->isTokenValid($token)) {
      throw new InvalidCsrfTokenException();
    }

    //check mail format
    $mail = explode('@', $credentials['username']);
    if (isset($mail[1])) {
      //only domain
      $domain = $this->entityManager->getRepository('App:Domain')->findOneBy(['domain' => strtolower($mail[1]), 'active' => 1]);
      if ($domain) {
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => strtolower($credentials['username'])]);
        $loginImap = $credentials['username'];
        //search imap login
        if ($user && $user->getImapLogin()) {
          //replace login for identification 
          $loginImap = $user->getImapLogin();
        }
        //on met à null le user afin de vérifier le mot de passe
        $user = null;

        $conStr = $domain->getSrvImap() . ":" . $domain->getImapPort() . $domain->getImapFlag();
        if ($domain->getImapNoValidateCert()) {
          $conStr .= '/novalidate-cert';
        }
        $mbox = @imap_open("{" . $conStr . "}", $loginImap, $credentials['password']);
        if (!imap_errors()) {
          $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => strtolower($credentials['username'])]);
          if (!$user) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
          }
        } else {
          throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
      } else {
        throw new CustomUserMessageAuthenticationException('Authentication failed.');
      }
    } else {
      $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => strtolower($credentials['username'])]);
      if (!$user) {
        // fail authentication with a custom error
        throw new CustomUserMessageAuthenticationException('Authentication failed.');
      }
    }

    return $user;
  }

  public function checkCredentials($credentials, UserInterface $user) {
    //if user have not password, don't check credential, the user (mail) is check with open_imap
    return (!$user->getPassword()) || ($this->passwordEncoder->isPasswordValid($user, $credentials['password']));
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
    $request->getSession()->set('originalUser', $token->getUser()->getUsername());
    $request->getSession()->set('_locale', 'fr');
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->router->generate('message'));    
  }

  protected function getLoginUrl() {
    return $this->router->generate('app_login');
  }

}
