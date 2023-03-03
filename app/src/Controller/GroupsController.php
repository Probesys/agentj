<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Entity\User;
use App\Form\GroupsType;
use App\Service\GroupService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 * @Route("/groups")
 */
class GroupsController extends AbstractController {

    use ControllerWBListTrait;

    private $em;
    private $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator) {
        $this->em = $em;
        $this->translator = $translator;
    }

    private function checkAccess($group) {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            if (!$group->getDomain()->getUsers()->contains($this->getUser())) {
                throw new AccessDeniedException();
            }
        }
    }

    /**
     * @Route("/", name="groups_index", methods="GET")
     */
    public function index(): Response {
        $groups = $this->em
                ->getRepository(Groups::class)
                ->findByDomain($this->getUser()->getDomains());

        return $this->render('groups/index.html.twig', ['groups' => $groups]);
    }

    /**
     * @Route("/new", name="groups_new", methods="GET|POST")
     */
    public function new(Request $request): Response {
        $group = new Groups();
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupsType::class, $group, [
                'actions' => $this->getWBListUserActions(),
                'action' => $this->generateUrl('groups_new'),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        } else {
            $form = $this->createForm(GroupsType::class, $group, ['actions' => $this->getWBListUserActions(), 'user' => $this->getUser(), 'action' => $this->generateUrl('groups_new')]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $labelExists = $this->checkNameforDomain($group->getName(), $form->get('domain')->getData());
            if ($labelExists) {
                return new Response(json_encode([
                            'status' => 'danger',
                            'message' => $this->translator->trans('Generics.flash.groupNameAllreadyExist'),
                ]));
            }

            $this->em->persist($group);

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail('@.');
                $this->em->persist($mailaddr);
            }
            $groupsWblist = new GroupsWblist();
            $groupsWblist->setMailaddr($mailaddr);
            $groupsWblist->setGroups($group);
            $groupsWblist->setWb($group->getWb());
            $this->em->persist($groupsWblist);

            $this->em->flush();
            return new Response(json_encode([
                        'status' => 'success',
                        'message' => $this->translator->trans('Generics.flash.addSuccess'),
                    ]), 200);
        }

        return $this->render('groups/new.html.twig', [
                    'group' => $group,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="groups_edit", methods="GET|POST")
     */
    public function edit(Request $request, Groups $group, GroupService $groupService, UserService $userService): Response {
        $this->checkAccess($group);
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupsType::class, $group, [
                'actions' => $this->getWBListUserActions(),
                'action' => $this->generateUrl('groups_edit', ['id' => $group->getId()]),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        } else {
            $form = $this->createForm(GroupsType::class, $group, [
                'actions' => $this->getWBListUserActions(),
                'user' => $this->getUser(),
                'action' => $this->generateUrl('groups_edit', ['id' => $group->getId()]),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        }

        $oldName = $group->getName();
        $olddomain = $group->getDomain();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($oldName != $group->getName() || $olddomain != $group->getDomain()) {
                $labelExists = $this->checkNameforDomain($group->getName(), $form->get('domain')->getData());
                if ($labelExists) {
                    return new Response(json_encode([
                                'status' => 'danger',
                                'message' => $this->translator->trans('Generics.flash.groupNameAllreadyExist'),
                    ]));
                }
            }

            $this->em->persist($group);

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));

            $groupsWblist = $this->em->getRepository(GroupsWblist::class)->findOneBy((['mailaddr' => $mailaddr, 'groups' => $group]));
            if (!$groupsWblist){
                $groupsWblist = new GroupsWblist();
                $groupsWblist->setMailaddr($mailaddr);
            }
                $groupsWblist->setGroups($group);
                $groupsWblist->setWb($group->getWb());
                $this->em->persist($groupsWblist);


            $this->em->flush();

            $groupService->updateWblist();
            foreach ($group->getUsers() as $user) {
                $userService->updateUserAndAliasPolicy($user);
            }


            $this->em->flush();

            return new Response(json_encode([
                        'status' => 'success',
                        'message' => $this->translator->trans('Generics.flash.editSuccess'),
                    ]), 200);
        }

        return $this->render('groups/edit.html.twig', [
                    'group' => $group,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/users", name="groups_list_users", methods="GET|POST")
     */
    public function listUsers(Request $request, Groups $group) {
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
    public function removeUser(Request $request, Groups $group, User $user, UserService $userService, GroupService $groupService): Response {
        if ($this->isCsrfTokenValid('removeUser' . $user->getId(), $request->query->get('_token'))) {
            $oldGroups = $user->getGroups()->toArray();
            $group->removeUser($user);

            $userAliases = $this->em->getRepository(User::class)->findBy(['originalUser' => $user->getId()]);
            foreach ($userAliases as $alias) {
                $group->removeUser($alias);
            }
            $this->em->flush();
            $userService->updateUserAndAliasPolicy($user);
            $groupService->updateWblist();

            $this->em->flush();
        }

        return $this->redirectToRoute('groups_list_users', ['id' => $group->getId()]);
    }

    /**
     * Vérify if a group with lable allready exists for a domain
     */
    public function checkNameforDomain(string $name, Domain $domain): bool {

        $group = $this->em->getRepository(Groups::class)->findOneBy([
            'domain' => $domain,
            'name' => $name
        ]);

        if ($group) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @Route("/{id}/delete", name="groups_delete", methods="GET")
     */
    public function delete(Request $request, Groups $group, UserService $userService, GroupService $groupService): Response {
        if ($this->isCsrfTokenValid('delete' . $group->getId(), $request->query->get('_token'))) {
            $groupUsers = $group->getUsers()->toArray();

            $this->em->remove($group);
            $this->em->flush();
            $groupService->updateWblist();
            foreach ($groupUsers as $user) {
                $userService->updateUserAndAliasPolicy($user);
            }
            $this->em->flush();
        }

        return $this->redirectToRoute('groups_index');
    }

    /**
     * @Route("/check-priority", name="groups_check_priority", methods="GET|POST")
     */
    public function checkPriorityExist(Request $request) {
        $domainId = $request->request->get('domainId');
        $domain = $this->em->getRepository(Domain::class)->find($domainId);

        $priority = $request->request->get('priority');
        $group = $this->em->getRepository(Groups::class)->findOneBy([
            'domain' => $domain,
            'priority' => $priority,
        ]);

        if (!$group || $group->getId() == $request->request->get('groupId')){
            return new JsonResponse(['status' => 'success']);    
        }
        
        return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Generics.messages.groupWithPriorityExists')]);
    }

}
