<?php

namespace App\Controller;

use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Repository\MsgrcptSearchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function renderHeader(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $domain = $user->getDomain();

        $sharedBoxes = $user->getOwnedSharedBoxes();
        return $this->render('header.html.twig', [
            'domain' => $domain,
            'sharedBoxes' => $sharedBoxes,
        ]);
    }
}
