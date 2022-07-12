<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webklex\PHPIMAP\ClientManager;

/**
 * @Route("/oAuthClient")
 */
class OAuthController extends AbstractController {

    #[Route('/', name: 'app_o_auth')]
    public function index(Request $request, UserRepository $userRepository): Response {
        $user = $userRepository->findOneBy(['email' => strtolower($request->query->get('login'))]);
         $oAuthConfig = $user->getDomain()->getOAuthConfig();
        $url = "https://login.microsoftonline.com/" . $oAuthConfig->getTenantId() . "/oauth2/v2.0/authorize?";
        $url .= "client_id=" . $oAuthConfig->getClientId();
        $url .= "&response_type=code%20id_token";
        $url .= "&redirect_uri=http%3A%2F%2Flocalhost:8090/oAuthClient/check";
        $url .= "&response_mode=form_post&nonce=abcde";
        $url .= "&scope=openid%20offline_access%20https%3A%2F%2Foutlook.office.com%2FIMAP.AccessAsUser.All";

        return $this->redirect(trim($url));

//        return $this->render('o_auth/index.html.twig', [
//            'controller_name' => 'OAuthController',
//        ]);
    }

    #[Route('/check', name: 'app_o_auth_check')]
    public function checkAuth(Request $request): Response {
//        $this->getLoginOAuthImap($request->request->get('id_token'));
        dd($request);
        return $this->redirect(trim($url));

//        return $this->render('o_auth/index.html.twig', [
//            'controller_name' => 'OAuthController',
//        ]);
    }    
    
    private function getLoginOAuthImap(String $token) {
//        dd($user);
//return new RedirectResponse($this->urlGenerator->generate('app_login',['step' => 2]));
/*@var $user User */
dd($token);
        $cm = new ClientManager();
        $client = $cm->make([
            'host' => 'outlook.office365.com',
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => false,
            'protocol' => 'imap',
            'username' => 'ctresvaux@5vrfgv.onmicrosoft.com',
            'password' => base64_encode("user=ctresvaux@5vrfgv.onmicrosoft.com^Aauth=Bearer " . $token . "^A^A"),
            'authentication' => "XOAUTH2",
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
}
