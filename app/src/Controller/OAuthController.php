<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    #[Route(path: '/connect/oauth', name: 'connect_oauth_start')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        $client = $clientRegistry->getClient('generic');
        return $client->redirect([], []);
    }

    #[Route(path: '/connect/oauth/check', name: 'connect_oauth_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry): void
    {
    }
}
