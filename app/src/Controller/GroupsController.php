<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Groups;
use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Form\GroupsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
/**
 * @Security("is_granted('ROLE_ADMIN')")
 * @Route("/groups")
 */
class GroupsController extends AbstractController
{
    use ControllerWBListTrait;

    private $em;
    private $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator) {
        $this->em = $em;
        $this->translator = $translator;
    }
    
    
    private function checkAccess($group)
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            if (!$group->getDomain()->getUsers()->contains($this->getUser())) {
                throw new AccessDeniedException();
            }
        }
    }

  /**
   * @Route("/", name="groups_index", methods="GET")
   */
    public function index(): Response
    {
        $groups = $this->em
            ->getRepository(Groups::class)
            ->findByDomain($this->getUser()->getDomains());

        return $this->render('groups/index.html.twig', ['groups' => $groups]);
    }

  /**
   * @Route("/new", name="groups_new", methods="GET|POST")
   */
    public function new(Request $request): Response
    {
        $group = new Groups();
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupsType::class, $group, [
            'actions' => $this->getWBListUserActions(),
            'action' => $this->generateUrl('groups_new'),
            ]);
        } else {
            $form = $this->createForm(GroupsType::class, $group, ['actions' => $this->getWBListUserActions(), 'user' => $this->getUser(), 'action' => $this->generateUrl('groups_new')]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($group);

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail('@.');
                $em->persist($mailaddr);
            }
            $groupsWblist = new GroupsWblist();
            $groupsWblist->setMailaddr($mailaddr);
            $groupsWblist->setGroups($group);
            $groupsWblist->setWb($group->getWb());
            $em->persist($groupsWblist);

            $em->flush();
            return $this->redirectToRoute('groups_index');
        }

        return $this->render('groups/new.html.twig', [
                'group' => $group,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/{id}/edit", name="groups_edit", methods="GET|POST")
   */
    public function edit(Request $request, Groups $group): Response
    {
        $this->checkAccess($group);
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupsType::class, $group, [
            'actions' => $this->getWBListUserActions(),
            'action' => $this->generateUrl('groups_edit', ['id' => $group->getId()]),
            ]);
        } else {
            $form = $this->createForm(GroupsType::class, $group, [
            'actions' => $this->getWBListUserActions(),
            'user' => $this->getUser(),
            'action' => $this->generateUrl('groups_edit', ['id' => $group->getId()]),
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($group);

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));

            $groupsWblist = $this->em->getRepository(GroupsWblist::class)->findOneBy((['mailaddr' => $mailaddr, 'groups' => $group]));
            if ($groupsWblist) {
                $groupsWblist->setGroups($group);
                $groupsWblist->setWb($group->getWb());
                $em->persist($groupsWblist);
            }


            $em->flush();

            $this->updatedWBListFromGroup($group->getId());

            return $this->redirectToRoute('groups_index', ['id' => $group->getId()]);
        }

        return $this->render('groups/edit.html.twig', [
                'group' => $group,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/{id}/users", name="groups_list_users", methods="GET|POST")
   */
    public function listUsers(Request $request, Groups $group)
    {
        $this->checkAccess($group);
        $users = $group->getUsers();
        return $this->render('groups/group_users.html.twig', [
                'group' => $group,
                'users' => $users
        ]);
    }

  /**
   * @Route("/{id}/removeUser/{user}/", name="group_remove_user", methods="GET")
   */
    public function removeUser(Request $request, Groups $group, User $user): Response
    {
        if ($this->isCsrfTokenValid('removeUser' . $user->getId(), $request->query->get('_token'))) {
            $em = $this->em;
          //check if alias
            if ($user->getOriginalUser()) {
                $user = $user->getOriginalUser();
            }
            $domainPolicy = $user->getDomain()->getPolicy();
            $userAliases = $this->em->getRepository(User::class)->findBy(['originalUser' => $user->getId()]);
            array_unshift($userAliases, $user);

            foreach ($userAliases as $userAlias) {
                $userAlias->setGroups(null);
                $userAlias->setPolicy($domainPolicy);
            }
            $em->flush();
            $this->updatedWBListFromGroup($group->getId());
        }

        return $this->redirectToRoute('groups_list_users', ['id' => $group->getId()]);
    }

  /**
   * @Route("/{id}/delete", name="groups_delete", methods="GET")
   */
    public function delete(Request $request, Groups $group): Response
    {
        if ($this->isCsrfTokenValid('delete' . $group->getId(), $request->query->get('_token'))) {
            $em = $this->em;
            $this->em->getRepository(User::class)->updatePolicyFromGroupToDomain($group->getId());
            $em->remove($group);
            $em->flush();
        }

        return $this->redirectToRoute('groups_index');
    }
}
