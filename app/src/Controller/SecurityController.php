<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return new RedirectResponse($this->container->get('router')->generate('homepage'));
        }

        // Get the login error if any.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Get the last username entered by the user.
        $lastUsername = $authenticationUtils->getLastUsername();

        // This is used when OAuth SSO is enabled, to let the administrators to
        // access the local login form.
        $forceDisplayForm = $request->query->getBoolean('local');

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'force_display_form' => $forceDisplayForm,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): RedirectResponse
    {
        return $this->redirectToRoute('homepage');
    }
}
