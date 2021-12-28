<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
                'logoUploaded' => $logoUploaded
        ]);
    }

  /**
   * @Route("/logout", name="app_logout")
   */
    public function logout()
    {
        return $this->redirectToRoute('homepage');
    }
}
