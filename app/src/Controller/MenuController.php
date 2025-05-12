<?php

namespace App\Controller;

use App\Entity\MessageStatus;
use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{

    public function __construct(private EntityManagerInterface $em) {
    }

    /**
     * @param array<string, mixed> $route_params
     */
    public function renderSlideMenu(string $route, array $route_params): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $alias = $this->em->getRepository(User::class)->findBy(['originalUser' => $user->getId()]);
        $msgs['untreated'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::UNTREATED, $alias);
        $msgs['authorized'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::AUTHORIZED, $alias);
        $msgs['banned'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::BANNED, $alias);
        $msgs['delete'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::DELETED, $alias);
        $msgs['restored'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::RESTORED, $alias);
        $msgs['error'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::ERROR, $alias);
        $msgs['spammed'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::SPAMMED, $alias);
        $msgs['virus'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::VIRUS, $alias);
        unset($alias);

        return $this->render(
            'navigation.html.twig',
            ['msgs' => $msgs, 'route' => $route, 'route_params' => $route_params]
        );
    }

    public function renderHeader(): Response
    {
        $logoUploaded = false;
        $logoImg = "";
        /** @var User $user */
        $user = $this->getUser();
        $domain = $user->getDomain();
        if ($domain && $domain->getLogo() && file_exists($this->getParameter('app.upload_directory') . $domain->getLogo())) {
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
