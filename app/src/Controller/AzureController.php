<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AzureController extends AbstractController
{
    /**
     * Action qui démarre le processus d'identification OAuth
     *
     * @Route("/connect/azure", name="connect_azure_start")
     */
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('azure')
//            ->redirect([], [])
            ->redirect([
                'public_profile', 'email' // the scopes you want to access
            ])
        ;
    }

    /**
     * Action de retour de l'OAuth (redirect_route dans la conf)
     *
     * @Route("/connect/azure/check", name="connect_azure_check")
     */
    public function connectCheckAction(ClientRegistry $clientRegistry): void
    {
    }
}
