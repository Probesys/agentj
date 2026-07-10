<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupRule;
use App\Entity\RuleAddress;
use App\Form\GroupRuleType;
use App\Repository\GroupRuleRepository;
use App\Service\GroupService;
use App\Service\RuleAddressService;
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
#[Route(path: '/group')]
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

    #[Route(path: '/{groupId}/rule', name: 'group_rule_index', methods: 'GET')]
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
                'defaultSortFieldName' => 'ra.email',
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

    #[Route(path: '/{groupId}/rule/new', name: 'group_rule_new', methods: 'GET|POST')]
    public function new(
        int $groupId,
        Request $request,
        RuleAddressService $ruleAddressService,
        GroupService $groupService,
    ): Response {
        $group = $this->em->getRepository(Group::class)->findOneBy((['id' => $groupId]));
        if (!$group) {
            throw $this->createNotFoundException('The group does not exist');
        }

        $groupRule = new GroupRule();

        $form = $this->createForm(GroupRuleType::class, null, [
            'action' => $this->generateUrl('group_rule_new', ['groupId' => $group->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $ruleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy((['email' => $data['email']]));
            if (!$ruleAddress) {
                $ruleAddress = new RuleAddress();
                $ruleAddress->setEmail($data['email']);
                $ruleAddress->setPriority($ruleAddressService->computePriority($data['email']));
                $em->persist($ruleAddress);
            } else {
                $groupRuleExists = $this->em->getRepository(GroupRule::class)->findOneBy(([
                    'ruleAddress' => $ruleAddress,
                    'group' => $group,
                ]));
                if ($groupRuleExists) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('group_rule_index', ['groupId' => $groupId]);
                }
            }

            $groupRule->setRuleAddress($ruleAddress);

            $groupRule->setGroup($group);
            $groupRule->setWbRule($data['wbRule']);

            $em->persist($groupRule);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));

            $groupService->updateSenderRule();

            return $this->redirectToRoute('group_rule_index', ['groupId' => $groupId]);
        }

        return $this->render('group_rule/new.html.twig', [
            'group_rule' => $groupRule,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/rule/{sid}/edit', name: 'group_rule_edit', methods: 'GET|POST')]
    public function edit(
        int $groupId,
        int $sid,
        Request $request,
        RuleAddressService $ruleAddressService,
        GroupService $groupService,
    ): Response {
        $groupRule = $this->em->getRepository(GroupRule::class)->findOneBy([
            'ruleAddress' => $sid,
            'group' => $groupId,
        ]);
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);
        $form = $this->createForm(GroupRuleType::class, null, [
            'action' => $this->generateUrl('group_rule_edit', ['groupId' => $group->getId(), 'sid' => $sid]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $ruleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy((['email' => $data['email']]));
            if (!$ruleAddress) {
                $ruleAddress = new RuleAddress();
                $ruleAddress->setEmail($data['email']);
                $ruleAddress->setPriority($ruleAddressService->computePriority($data['email']));
                $em->persist($ruleAddress);
            } else {
                $groupRuleExists = $this->em->getRepository(GroupRule::class)->findOneBy(([
                    'ruleAddress' => $ruleAddress,
                    'group' => $group,
                ]));
                if ($groupRuleExists && $ruleAddress != $groupRule->getRuleAddress()) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('group_rule_new', ['groupId' => $groupId]);
                }
            }

            $groupRule->setRuleAddress($ruleAddress);
            $groupRule->setGroup($group);
            $groupRule->setWbRule($data['wbRule']);

            $em->persist($groupRule);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.ruleUpdated'));

            $groupService->updateSenderRule();

            return $this->redirectToRoute('group_rule_index', ['groupId' => $groupId]);
        } else {
            $form->get('email')->setData($groupRule->getRuleAddress()->getEmail());
            $form->get('wbRule')->setData($groupRule->getWbRule());
        }

        return $this->render('group_rule/edit.html.twig', [
            'group_rule' => $groupRule,
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{groupId}/rule/{sid}/delete', name: 'group_rule_delete', methods: 'POST')]
    public function delete(int $groupId, int $sid, Request $request, GroupService $groupService): RedirectResponse
    {
        $group = $this->em->getRepository(Group::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);

        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete' . $groupId . '_' . $sid, $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('group_rule_index', ['groupId' => $groupId]);
        }

        $groupRule = $this->em->getRepository(GroupRule::class)->findOneBy(([
            'ruleAddress' => $sid,
            'group' => $groupId,
        ]));

        if ($groupRule) {
            $this->em->remove($groupRule);
            $this->em->flush();
        }

        $groupService->updateSenderRule();

        return $this->redirectToRoute('group_rule_index', ['groupId' => $groupId]);
    }
}
