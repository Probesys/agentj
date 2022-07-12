<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

  /**
   * @Route("/login", name="app_login")
   */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
      // get the login error if there is one
        if ($this->getUser()) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }
        $error = $authenticationUtils->getLastAuthenticationError();
      // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $logoUploaded = file_exists($this->getParameter('app.upload_directory') . 'logo.png');
        return $this->renderForm('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
                'logoUploaded' => $logoUploaded
        ]);
    }
    
  /**
   * @Route("/check-auth-mode", name="app_check_auth_mode")
   */
    public function getAuthMode(Request $request, UserRepository $userRepository): Response
    {

        $authType = 'IMAP';
        if ($request->query->has('_username')){
            $user = $userRepository->findOneBy(['email' => strtolower($request->query->get('_username'))]);
//            dd($request->query->get('_username'));
            $authType = $user && $user->getDomain() ?? $user->getDomain()->getAuthMode();
        }
        return new JsonResponse(['authType' => $authType]);
    }
    

  /**
   * @Route("/logout", name="app_logout")
   */
    public function logout()
    {
        return $this->redirectToRoute('homepage');
    }
}
