<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupRule;
use App\Entity\Mailaddr;
use App\Form\GroupRuleType;
use App\Repository\GroupRuleRepository;
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
class GroupRuleController extends AbstractController
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

    #[Route(path: '/{groupId}/rules', name: 'groups_rules_index', methods: 'GET')]
    public function index(
        int $groupId,
        GroupRuleRepository $groupRuleRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $group = $this->em->getRepository(Group::class)->find($groupId);
        $this->checkAccess($group);

        $searchKey = $request->query->getString('search', '');
        $groupRulesSearchQuery = $groupRuleRepository->getSearchQuery(
            group: $group,
            searchKey: $searchKey
        );

        $perPage = (int)$this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $groupRules = $paginator->paginate(
            $groupRulesSearchQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'madr.email',
                'defaultSortDirection' => 'asc',
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
            ],
        );

        return $this->render('group_rule/index.html.twig', [
            'group_rules' => $groupRules,
            'group' => $group,
        ]);
    }

    #[Route(path: '/{groupId}/rules/new', name: 'groups_rules_new', methods: 'GET|POST')]
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

        $groupRule = new GroupRule();

        $form = $this->createForm(GroupRuleType::class, null, [
            'action' => $this->generateUrl('groups_rules_new', ['groupId' => $group->getId()]),
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
                $groupRuleExists = $this->em->getRepository(GroupRule::class)->findOneBy(([
                    'mailaddr' => $mailaddr,
                    'group' => $group,
                ]));
                if ($groupRuleExists) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('groups_rules_index', ['groupId' => $groupId]);
                }
            }

            $groupRule->setMailaddr($mailaddr);

            $groupRule->setGroup($group);
            $groupRule->setWbRule($data['wbRule']);

            $em->persist($groupRule);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));

            $groupService->updateWblist();

            return $this->redirectToRoute('groups_rules_index', ['groupId' => $groupId]);
        }

        return $this->render('group_rule/new.html.twig', [
            'group_rule' => $groupRule,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/rules/{sid}/edit', name: 'groups_rules_edit', methods: 'GET|POST')]
    public function edit(
        int $groupId,
        int $sid,
        Request $request,
        MailaddrService $mailaddrService,
        GroupService $groupService,
    ): Response {
        $groupRule = $this->em->getRepository(GroupRule::class)->findOneBy([
            'mailaddr' => $sid,
            'group' => $groupId,
        ]);
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);
        $form = $this->createForm(GroupRuleType::class, null, [
            'action' => $this->generateUrl('groups_rules_edit', ['groupId' => $group->getId(), 'sid' => $sid]),
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
                $groupRuleExists = $this->em->getRepository(GroupRule::class)->findOneBy(([
                    'mailaddr' => $mailaddr,
                    'group' => $group,
                ]));
                if ($groupRuleExists && $mailaddr != $groupRule->getMailaddr()) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('groups_rules_new', ['groupId' => $groupId]);
                }
            }

            $groupRule->setMailaddr($mailaddr);
            $groupRule->setGroup($group);
            $groupRule->setWbRule($data['wbRule']);

            $em->persist($groupRule);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.ruleUpdated'));

            $groupService->updateWblist();

            return $this->redirectToRoute('groups_rules_index', ['groupId' => $groupId]);
        } else {
            $form->get('email')->setData($groupRule->getMailaddr()->getEmail());
            $form->get('wbRule')->setData($groupRule->getWbRule());
        }

        return $this->render('group_rule/edit.html.twig', [
            'group_rule' => $groupRule,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/rules/{sid}/delete', name: 'groups_rules_delete', methods: 'POST')]
    public function delete(int $groupId, int $sid, Request $request, GroupService $groupService): RedirectResponse
    {
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);

        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete' . $groupId . '_' . $sid, $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('groups_rules_index', ['groupId' => $groupId]);
        }

        $groupRule = $this->em->getRepository(GroupRule::class)->findOneBy(([
            'mailaddr' => $sid,
            'group' => $groupId,
        ]));

        if ($groupRule) {
            $this->em->remove($groupRule);
            $this->em->flush();
        }

        $groupService->updateWblist();

        return $this->redirectToRoute('groups_rules_index', ['groupId' => $groupId]);
    }
}
