<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\UserPreferencesType;
use App\Repository\GroupsRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountController extends AbstractController
{
    use ControllerWBListTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/account', name: 'account')]
    public function index(Request $request, GroupsRepository $groupsRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserPreferencesType::class, $user, [
            'action' => $this->generateUrl('account'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($this->getUser());
            $this->em->flush();
        }

        $wbDomain = $this->em->getRepository(Wblist::class)->getDefaultDomainWBList($user->getDomain());
        $domainDefaulWb = array_keys(array_filter(
            $this->getWBListDomainActions(),
            function ($item) use ($wbDomain) {
                return $item == $wbDomain;
            }
        ))[0];

        $groupDefaulWb = null;
        if ($user->getGroups() && count($user->getGroups()) > 0) {
            $defaultGroup = $groupsRepository->getMainUserGroup($user);
            if ($defaultGroup) {
                $wbGroup = $defaultGroup->getWb();
                $groupDefaulWb = array_keys(array_filter(
                    $this->getWBListUserActions(),
                    function ($item) use ($wbGroup) {
                        return $item == $wbGroup;
                    }
                ))[0];
            }
        }

        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
            'domainDefaulWb' => $domainDefaulWb,
            'groupDefaulWb' => $groupDefaulWb,
            'form' => $form->createView(),
        ]);
    }
}
