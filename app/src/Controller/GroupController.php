<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\GroupRule;
use App\Entity\RuleAddress;
use App\Entity\User;
use App\Form\GroupType;
use App\Repository\DomainRepository;
use App\Repository\GroupRepository;
use App\Repository\GroupRuleRepository;
use App\Repository\RuleAddressRepository;
use App\Repository\UserRepository;
use App\Service\GroupService;
use App\Service\Referrer;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/groups')]
class GroupController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private DomainRepository $domainsRepository,
        private Referrer $referrer,
    ) {
    }

    private function checkAccess(Group $group): void
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            if (!$group->getDomain()->getUsers()->contains($this->getUser())) {
                throw new AccessDeniedException();
            }
        }
    }

    #[Route(path: '/', name: 'group_index', methods: 'GET')]
    public function index(
        Request $request,
        GroupRepository $groupRepository,
        PaginatorInterface $paginator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $domains = $this->domainsRepository->findAll();
        } else {
            $domains = $user->getDomains()->toArray();
        }

        $groupsQuery = $groupRepository->getSearchQuery(
            domains: $domains,
            searchKey: $request->query->getString('search', '')
        );

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $groups = $paginator->paginate(
            $groupsQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'g.name',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('group/index.html.twig', ['groups' => $groups]);
    }

    #[Route(path: '/new', name: 'group_new', methods: 'GET|POST')]
    public function new(Request $request): Response
    {
        $group = new Group();
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupType::class, $group, [
                'action' => $this->generateUrl('group_new'),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        } else {
            $form = $this->createForm(GroupType::class, $group, [
                'user' => $this->getUser(),
                'action' => $this->generateUrl('group_new'),
            ]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $labelExists = $this->checkNameforDomain($group->getName(), $form->get('domain')->getData());
            if ($labelExists) {
                return new JsonResponse([
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.groupNameAlreadyExist'),
                ]);
            }

            $this->em->persist($group);

            $ruleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy((['email' => '@.']));
            if (!$ruleAddress) {
                $ruleAddress = new RuleAddress();
                $ruleAddress->setEmail('@.');
                $this->em->persist($ruleAddress);
            }
            $groupRule = new GroupRule();
            $groupRule->setRuleAddress($ruleAddress);
            $groupRule->setGroup($group);
            $groupRule->setWb($group->getWb());
            $this->em->persist($groupRule);

            $this->em->flush();
            return new JsonResponse([
                'status' => 'success',
                'message' => $this->translator->trans('Generics.flash.addSuccess'),
            ], 200);
        }

        return $this->render('group/new.html.twig', [
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'group_edit', methods: 'GET|POST')]
    public function edit(
        Request $request,
        Group $group,
        GroupService $groupService,
        UserService $userService,
        GroupRuleRepository $groupRuleRepository,
        RuleAddressRepository $ruleAddressRepository,
    ): Response {
        $this->checkAccess($group);
        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $form = $this->createForm(GroupType::class, $group, [
                'action' => $this->generateUrl('group_edit', ['id' => $group->getId()]),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        } else {
            $form = $this->createForm(GroupType::class, $group, [
                'user' => $this->getUser(),
                'action' => $this->generateUrl('group_edit', ['id' => $group->getId()]),
                'attr' => ['class' => 'modal-ajax-form']
            ]);
        }

        $oldName = $group->getName();
        $oldDomain = $group->getDomain();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($oldName !== $group->getName() || $oldDomain !== $group->getDomain()) {
                $labelExists = $this->checkNameforDomain($group->getName(), $form->get('domain')->getData());
                if ($labelExists) {
                    return new JsonResponse([
                        'status' => 'danger',
                        'message' => $this->translator->trans('Generics.flash.groupNameAlreadyExist'),
                    ]);
                }
            }

            $this->em->persist($group);

            $ruleAddress = $ruleAddressRepository->findOneBy((['email' => '@.']));

            $groupRule = $groupRuleRepository->findOneBy((['ruleAddress' => $ruleAddress, 'group' => $group]));
            if (!$groupRule) {
                $groupRule = new GroupRule();
                $groupRule->setRuleAddress($ruleAddress);
            }
            $groupRule->setGroup($group);
            $groupRule->setWb($group->getWb());
            $this->em->persist($groupRule);


            $this->em->flush();

            $groupService->updateSenderRules();
            foreach ($group->getUsers() as $user) {
                $userService->updateUserAndAliasPolicy($user);
            }


            $this->em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => $this->translator->trans('Generics.flash.editSuccess'),
            ], 200);
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/users', name: 'group_list_users', methods: 'GET|POST')]
    public function listUsers(
        Group $group,
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {

        $this->checkAccess($group);

        $searchKey = $request->query->getString('search', '');
        $usersQuery = $userRepository->getSearchByGroupQuery($group, $searchKey);

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $users = $paginator->paginate(
            $usersQuery,
            $request->query->getInt('page', 1),
            $perPage
        );

        return $this->render('group/group_users.html.twig', [
            'group' => $group,
            'users' => $users
        ]);
    }

    #[Route(path: '/{id}/removeUser/{user}/', name: 'group_remove_user', methods: 'POST')]
    public function removeUser(
        Request $request,
        Group $group,
        User $user,
        UserService $userService,
        GroupService $groupService,
        UserRepository $userRepository,
    ): Response {

        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('removeUser' . $user->getId(), $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('group_list_users', ['id' => $group->getId()]);
        }

        $group->removeUser($user);

        $userAliases = $userRepository->findBy(['originalUser' => $user->getId()]);
        foreach ($userAliases as $alias) {
            $group->removeUser($alias);
        }
        $this->em->flush();
        $userService->updateUserAndAliasPolicy($user);
        $groupService->updateSenderRules();

        $this->em->flush();

        return $this->redirectToRoute('group_list_users', ['id' => $group->getId()]);
    }

    /**
     * Verify if a group with label already exists for a domain
     */
    public function checkNameforDomain(string $name, Domain $domain): bool
    {

        $group = $this->em->getRepository(Group::class)->findOneBy([
            'domain' => $domain,
            'name' => $name
        ]);

        if ($group) {
            return true;
        }

        return false;
    }

    #[Route(path: '/{id}/delete', name: 'group_delete', methods: 'POST')]
    public function delete(
        Request $request,
        Group $group,
        UserService $userService,
        GroupService $groupService,
    ): Response {
        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete' . $group->getId(), $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('group_index');
        }

        $groupUsers = $group->getUsers()->toArray();

        $this->em->remove($group);
        $this->em->flush();

        $groupService->updateSenderRules();

        foreach ($groupUsers as $user) {
            $userService->updateUserAndAliasPolicy($user);
        }

        $this->em->flush();

        return $this->redirectToRoute('group_index');
    }

    #[Route(path: '/batchDelete', name: 'groups_batch_delete', methods: 'POST')]
    public function batchDeleteEmail(Request $request): Response
    {
        $csrfToken = $request->request->getString('_csrf_token', 'delete');

        if (!$this->isCsrfTokenValid('delete group', $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirect($this->referrer->get());
        }

        foreach ($request->request->all('id') as $id) {
            $group = $this->em->getRepository(Group::class)->find($id);
            if ($group) {
                $this->em->remove($group);
            }
        }
        $this->em->flush();

        return $this->redirect($this->referrer->get());
    }

    #[Route(path: '/check-priority', name: 'group_check_priority', methods: 'GET|POST')]
    public function checkPriorityExist(Request $request): JsonResponse
    {
        $domainId = $request->request->get('domainId');
        $domain = $this->em->getRepository(Domain::class)->find($domainId);

        $priority = $request->request->get('priority');
        $group = $this->em->getRepository(Group::class)->findOneBy([
            'domain' => $domain,
            'priority' => $priority,
        ]);

        if (!$group || $group->getId() == $request->request->get('groupId')) {
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => $this->translator->trans('Generics.messages.groupWithPriorityExists'),
        ]);
    }
}
