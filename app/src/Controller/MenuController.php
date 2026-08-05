<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function renderHeader(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $domain = $user->getDomain();

        return $this->render('header.html.twig', [
            'domain' => $domain,
        ]);
    }
}
