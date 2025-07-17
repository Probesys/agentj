<?php

namespace App\Controller;

use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Repository\MsgrcptSearchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    /**
     * @param array<string, mixed> $routeParams
     */
    public function renderSlideMenu(
        string $route,
        array $routeParams,
        MsgrcptSearchRepository $msgrcptSearchRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $msgs['untreated'] = $msgrcptSearchRepository->countByType($user, MessageStatus::UNTREATED);
        $msgs['authorized'] = $msgrcptSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
        $msgs['banned'] = $msgrcptSearchRepository->countByType($user, MessageStatus::BANNED);
        $msgs['delete'] = $msgrcptSearchRepository->countByType($user, MessageStatus::DELETED);
        $msgs['restored'] = $msgrcptSearchRepository->countByType($user, MessageStatus::RESTORED);
        $msgs['error'] = $msgrcptSearchRepository->countByType($user, MessageStatus::ERROR);
        $msgs['spammed'] = $msgrcptSearchRepository->countByType($user, MessageStatus::SPAMMED);
        $msgs['virus'] = $msgrcptSearchRepository->countByType($user, MessageStatus::VIRUS);

        return $this->render(
            'navigation.html.twig',
            ['msgs' => $msgs, 'route' => $route, 'route_params' => $routeParams]
        );
    }

    public function renderHeader(): Response
    {
        $logoUploaded = false;
        $logoImg = "";
        /** @var User $user */
        $user = $this->getUser();
        $domain = $user->getDomain();
        if (
            $domain &&
            $domain->getLogo() &&
            file_exists($this->getParameter('app.upload_directory') . $domain->getLogo())
        ) {
            $logoUploaded = true;
            $logoImg = $domain->getLogo();
        } else {
            $logoUploaded = false;
            $logoImg = 'agent-j-logo-desktop.svg';
        }

        $sharedBoxes = $user->getOwnedSharedBoxes();
        return $this->render(
            'header.html.twig',
            [
                        'logoUploaded' => $logoUploaded,
                        'logoImg' => $logoImg,
                        'sharedBoxes' => $sharedBoxes
                    ]
        );
    }
}
