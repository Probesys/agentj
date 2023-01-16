<?php

namespace App\Controller;

use App\Entity\MessageStatus;
use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MenuController extends AbstractController
{

    private $translator;

    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
        
    
    public function renderSlideMenu($route, $route_params)
    {


        $alias = $this->em->getRepository(User::class)->findBy(['originalUser' => $this->getUser()->getId()]);
        $msgs['untreated'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::UNTREATED, $alias);
        $msgs['authorized'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::AUTHORIZED, $alias);
        $msgs['banned'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::BANNED, $alias);
        $msgs['delete'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::DELETED, $alias);
        $msgs['restored'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::RESTORED, $alias);
        $msgs['error'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::ERROR, $alias);
        $msgs['spammed'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::SPAMMED, $alias);
        $msgs['virus'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::VIRUS, $alias);
        unset($alias);

        return $this->render(
            'navigation.html.twig',
            ['msgs' => $msgs, 'route' => $route, 'route_params' => $route_params]
        );
    }

    public function renderHeader()
    {
        $logoUploaded = false;
        $logoImg = "";
        $domain = $this->getUser()->getDomain();
        if ($domain && $domain->getLogo() && file_exists($this->getParameter('app.upload_directory') . $domain->getLogo())) {
            $logoUploaded = true;
            $logoImg = $domain->getLogo();
        } else {
            $logoUploaded = false;
            $logoImg = 'agent-j-logo-desktop.svg';
        }

      /* @var $user User */
        $sharedBoxes = $this->getUser()->getOwnedSharedBoxes();
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
