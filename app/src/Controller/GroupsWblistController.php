<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Form\GroupsWblistType;
use App\Repository\GroupsWblistRepository;
use App\Service\GroupService;
use App\Service\MailaddrService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/groups')]
class GroupsWblistController extends AbstractController
{
    private EntityManagerInterface $em;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    private function checkAccess(Group $group): void
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            if (!$group->getDomain()->getUsers()->contains($this->getUser())) {
                throw new AccessDeniedException();
            }
        }
    }

    #[Route(path: '/{groupId}/wblist', name: 'group_wblist_index', methods: 'GET')]
    public function index(
        int $groupId,
        GroupsWblistRepository $groupsWblistRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $group = $this->em->getRepository(Group::class)->find($groupId);
        $this->checkAccess($group);

        $searchKey = $request->query->getString('search', '');
        $groupsWblistSearchQuery = $groupsWblistRepository->getSearchQuery(
            group: $group,
            searchKey: $searchKey
        );

        $perPage = (int)$this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $groupsWblists = $paginator->paginate(
            $groupsWblistSearchQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'madr.email',
                'defaultSortDirection' => 'asc',
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
            ]
        );

        return $this->render('groups_wblist/index.html.twig', [
            'groups_wblists' => $groupsWblists,
            'group' => $group,
        ]);
    }

    #[Route(path: '/{groupId}/wblist/new', name: 'group_wblist_new', methods: 'GET|POST')]
    public function new(
        int $groupId,
        Request $request,
        MailaddrService $mailaddrService,
        GroupService $groupService,
    ): Response {
        $group = $this->em->getRepository(Group::class)->findOneBy((['id' => $groupId]));
        if (!$group) {
            throw $this->createNotFoundException('The group does not exist');
        }

        $groupsWblist = new GroupsWblist();

        $form = $this->createForm(GroupsWblistType::class, null, [
            'action' => $this->generateUrl('group_wblist_new', ['groupId' => $group->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $data['email']]));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail($data['email']);
                $mailaddr->setPriority($mailaddrService->computePriority($data['email']));
                $em->persist($mailaddr);
            } else {
                $groupsWblistexist = $this->em->getRepository(GroupsWblist::class)->findOneBy(([
                    'mailaddr' => $mailaddr,
                    'group' => $group,
                ]));
                if ($groupsWblistexist) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('group_wblist_index', ['groupId' => $groupId]);
                }
            }

            $groupsWblist->setMailaddr($mailaddr);

            $groupsWblist->setGroup($group);
            $groupsWblist->setWbRule($data['wbRule']);

            $em->persist($groupsWblist);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));

            $groupService->updateWblist();

            return $this->redirectToRoute('group_wblist_index', ['groupId' => $groupId]);
        }

        return $this->render('groups_wblist/new.html.twig', [
            'groups_wblist' => $groupsWblist,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/wblist/{sid}/edit', name: 'group_wblist_edit', methods: 'GET|POST')]
    public function edit(
        int $groupId,
        int $sid,
        Request $request,
        MailaddrService $mailaddrService,
        GroupService $groupService,
    ): Response {
        $groupWbList = $this->em->getRepository(GroupsWblist::class)->findOneBy([
            'mailaddr' => $sid,
            'group' => $groupId,
        ]);
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);
        $form = $this->createForm(GroupsWblistType::class, null, [
            'action' => $this->generateUrl('group_wblist_edit', ['groupId' => $group->getId(), 'sid' => $sid]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $data['email']]));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail($data['email']);
                $mailaddr->setPriority($mailaddrService->computePriority($data['email']));
                $em->persist($mailaddr);
            } else {
                $groupsWblistexist = $this->em->getRepository(GroupsWblist::class)->findOneBy(([
                    'mailaddr' => $mailaddr,
                    'group' => $group,
                ]));
                if ($groupsWblistexist && $mailaddr != $groupWbList->getMailaddr()) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('group_wblist_new', ['groupId' => $groupId]);
                }
            }

            $groupWbList->setMailaddr($mailaddr);
            $groupWbList->setGroup($group);
            $groupWbList->setWbRule($data['wbRule']);

            $em->persist($groupWbList);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.ruleUpdated'));

            $groupService->updateWblist();

            return $this->redirectToRoute('group_wblist_index', ['groupId' => $groupId]);
        } else {
            $form->get('email')->setData($groupWbList->getMailaddr()->getEmail());
            $form->get('wbRule')->setData($groupWbList->getWbRule());
        }

        return $this->render('groups_wblist/edit.html.twig', [
            'groups_wblist' => $groupWbList,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/wblist/{sid}/delete', name: 'group_wblist_delete', methods: 'POST')]
    public function delete(int $groupId, int $sid, Request $request, GroupService $groupService): RedirectResponse
    {
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);

        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete' . $groupId . '_' . $sid, $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('group_wblist_index', ['groupId' => $groupId]);
        }

        $groupsWblist = $this->em->getRepository(GroupsWblist::class)->findOneBy(([
            'mailaddr' => $sid,
            'group' => $groupId,
        ]));

        if ($groupsWblist) {
            $this->em->remove($groupsWblist);
            $this->em->flush();
        }

        $groupService->updateWblist();

        return $this->redirectToRoute('group_wblist_index', ['groupId' => $groupId]);
    }
}
