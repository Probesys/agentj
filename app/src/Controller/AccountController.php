<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wblist;
use App\Form\UserPreferencesType;
use App\Repository\GroupsRepository;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
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

        $domainWblist = $this->em->getRepository(Wblist::class)->getDefaultDomainWBList($user->getDomain());

        $defaultGroup = $groupsRepository->getMainUserGroup($user);

        return $this->render('account/index.html.twig', [
            'form' => $form,
            'domainWblist' => $domainWblist,
            'defaultGroup' => $defaultGroup,
        ]);
    }
}
