<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Form\ActionsFilterType;
use App\Form\ImportType;
use App\Form\SenderRuleType;
use App\Repository\DomainRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use App\Service\LogService;
use App\Service\Referrer;
use App\Service\SenderRuleService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class SenderRuleController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        private Referrer $referrer,
        private UserRepository $userRepository,
    ) {
    }

    #[Route(path: '/rules/{type}', name: 'sender_rules_index')]
    public function index(string $type, Request $request, PaginatorInterface $paginator): Response
    {
        if ($type !== 'W' && $type !== 'B') {
            throw $this->createNotFoundException("Type {$type} is invalid");
        }

        $actionLabel = $type === 'B' ?
            new TranslatableMessage('Message.Actions.Unlock') :
            new TranslatableMessage('Message.Actions.Delete');
        $filterForm = $this->createForm(ActionsFilterType::class, null, [
            'avalaibleActions' => [$actionLabel->getMessage() => 'delete'],
            'action' => $this->generateUrl('sender_rules_batch'),
        ]);

        $sortField = $request->query->getString('sortField');
        if (!in_array($sortField, ['emailuser', 'email', 'wb.datemod'])) {
            $sortField = 'email';
        }
        $sortDirection = $request->query->getString('sortDirection');
        if ($sortDirection !== 'asc' && $sortDirection !== 'desc') {
            $sortDirection = 'asc';
        }

        $query = trim($request->query->getString('search'));

        /** @var User $user */
        $user = $this->getUser();
        $title = '';
        $senderRule = $this->em->getRepository(SenderRule::class)->search($type, $user, $query, [
            'field' => $sortField,
            'direction' => $sortDirection,
        ]);

        switch ($type) {
            case "W":
                $title = $this->translator->trans('Navigation.whitelist');
                break;
            case "B":
                $title = $this->translator->trans('Navigation.blacklist');
                break;
        }
        $totalItemFound = count($senderRule);

        // Retrieve perPage from the request or use the default value
        $perPage = $request->query->getInt('per_page', (int) $this->getParameter('app.per_page_global'));

        // Set the initial value of perPage in the form
        $filterForm->get('per_page')->setData($perPage);

        $senderRules = $paginator->paginate(
            $senderRule,
            $request->query->getInt('page', 1) /* page number */,
            $perPage
        );
        return $this->render('sender_rule/index.html.twig', [
            'controller_name' => 'SenderRuleController',
            'senderRules' => $senderRules,
            'senderRuleType' => $type,
            'title' => $title,
            'totalItemFound' => $totalItemFound,
            'filter_form' => $filterForm->createView()
        ]);
    }

    #[Route(path: '/rules/new/{type}', name: 'sender_rules_new', requirements: ['type' => 'W|B'], methods: 'GET')]
    public function newSenderRule(string $type, DomainRepository $domainRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createSenderRuleForm($type, $user, $domainRepository);

        return $this->render('sender_rule/new.html.twig', [
            'senderRuleType' => $type,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/rules/new/{type}', name: 'sender_rules_create', requirements: ['type' => 'W|B'], methods: 'POST')]
    public function createSenderRule(
        string $type,
        Request $request,
        DomainRepository $domainRepository,
        SenderRuleService $senderRuleService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createSenderRuleForm($type, $user, $domainRepository);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = iterator_to_array($form->getErrors(true), false);

            return new JsonResponse([
                'status' => 'danger',
                'message' => $errors
                    ? $errors[0]->getMessage()
                    : $this->translator->trans('Generics.flash.genericFormError'),
            ]);
        }

        /** @var array{email: string, domain?: Domain} $data */
        $data = $form->getData();
        $recipient = $user;
        $source = SenderRule::TYPE_USER;
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($isAdmin) {
            $domain = $data['domain'] ?? null;
            $domainName = $domain?->getDomain();
            $recipient = $domainName ? $this->userRepository->findDomainUser($domainName) : null;
            $source = SenderRule::TYPE_ADMIN;
        }

        $created = $recipient && ($isAdmin
            ? $senderRuleService->createOrUpdateForRecipient(
                $data['email'],
                $recipient,
                $type === 'W' ? 'accept' : 'block',
                $source,
            )
            : $senderRuleService->createOrUpdateForUserAndAliases(
                $data['email'],
                $recipient,
                $type === 'W' ? 'accept' : 'block',
                $source,
            ));

        if (!$created) {
            return new JsonResponse([
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.genericFormError'),
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => $this->translator->trans(
                $type === 'W' ? 'Message.Flash.senderAuthorized' : 'Message.Flash.senderBanned',
            ),
        ]);
    }

    /**
     * @return FormInterface<array{email: string, domain?: Domain}>
     */
    private function createSenderRuleForm(
        string $type,
        User $user,
        DomainRepository $domainRepository,
    ): FormInterface {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        return $this->createForm(SenderRuleType::class, null, [
            'action' => $this->generateUrl('sender_rules_create', ['type' => $type]),
            'attr' => ['class' => 'modal-ajax-form'],
            'domains' => $isAdmin ? $domainRepository->findActiveForUser($user) : [],
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route(path: '/rules/{rid}/{sid}/{priority}/delete', name: 'sender_rules_delete', methods: 'GET')]
    public function deleteAction(
        int $rid,
        int $sid,
        int $priority,
        SenderRuleRepository $senderRuleRepository,
        Request $request
    ): RedirectResponse {
        if ($this->isCsrfTokenValid('delete_sender_rule' . $rid . $sid, $request->query->get('_token'))) {
            $this->deleteSenderRule($rid, $sid, $priority, $senderRuleRepository);
            $this->addFlash('success', $this->translator->trans('Message.Flash.deleteSuccesFull'));
        } else {
            $this->addFlash('error', 'Invalid csrf token');
        }

        return new RedirectResponse($this->referrer->get());
    }

    private function deleteSenderRule(
        int $userId,
        int $senderRuleAddressId,
        int $priority,
        SenderRuleRepository $senderRuleRepository,
    ): void {
        $user = $this->userRepository->find($userId);
        $userAndAliases = $user ? $this->userRepository->findUserAndAliases($user) : [];

        foreach ($userAndAliases as $userOrAlias) {
            $senderRuleRepository->delete($userOrAlias->getId(), $senderRuleAddressId, $priority);
        }
    }

    #[Route(
        path: '/rules/batch/{action}',
        name: 'sender_rules_batch',
        methods: 'POST',
        options: ['expose' => true],
    )]
    public function batchSenderRuleAction(Request $request, ?string $action = null): RedirectResponse
    {
        $em = $this->em;
        if ($action) {
            $logService = new LogService($em);
            foreach ($request->request->all('id') as $obj) {
                $mailInfo = json_decode($obj);
                switch ($action) {
                    case 'delete':
                        $this->deleteSenderRule(
                            $mailInfo[0],
                            $mailInfo[1],
                            $mailInfo[2],
                            $em->getRepository(SenderRule::class)
                        );
                        $logService->addLog('delete batch sender rule', $mailInfo[1]);
                        break;
                }
            }
        }

        return new RedirectResponse($this->referrer->get());
    }

    #[Route(path: '/rules/admin/import/{type}', name: 'sender_rules_import', options: ['expose' => true])]
    public function importSenderRuleAction(
        Request $request,
        string $type,
        DomainRepository $domainRepository,
        SenderRuleService $senderRuleService,
    ): Response {
        if ($type !== 'W' && $type !== 'B') {
            return new Response("Type has to be either `W` or `B`", 422);
        }
        $rule = $type === 'W' ? 'accept' : 'block';

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('sender_rules_import', ['type' => $type]),
            'domains' => $domainRepository->findActiveForUser($user),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/plain") {
                $filename = 'import-sender-rules-agentj-' . time() . ".txt";
                $file = $fileUpload->move('/tmp/', $filename);
                $senderRuleService->importFile(
                    $file->getPathname(),
                    $form->get('domain')->getData(),
                    $rule,
                );
                $this->addFlash('success', new TranslatableMessage('Entities.Import.SenderRule.success'));
            } else {
                $this->addFlash('danger', new TranslatableMessage('Generics.flash.BadImportFormat'));
            }

            return new RedirectResponse($this->referrer->get());
        }

        return $this->render('import/index_sender_rule.html.twig', [
            'controller_name' => 'ImportController',
            'form' => $form,
            'senderRuleType' => $type,
        ]);
    }
}
