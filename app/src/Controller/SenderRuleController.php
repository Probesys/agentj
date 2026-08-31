<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\RuleAddress;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Form\ActionsFilterType;
use App\Form\ImportType;
use App\Repository\SenderRuleRepository;
use App\Service\LogService;
use App\Service\Referrer;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        $query = trim($request->query->getString('search'));

        /** @var User $user */
        $user = $this->getUser();
        $title = '';
        $senderRuleQuery = $this->em->getRepository(SenderRule::class)->getSearchQuery($type, $user, $query);

        switch ($type) {
            case "W":
                $title = $this->translator->trans('Navigation.whitelist');
                break;
            case "B":
                $title = $this->translator->trans('Navigation.blacklist');
                break;
        }

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;


        $senderRules = $paginator->paginate(
            $senderRuleQuery,
            $request->query->getInt('page', 1) /* page number */,
            $perPage,
            [
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
            ]
        );

        return $this->render('sender_rule/index.html.twig', [
            'controller_name' => 'SenderRuleController',
            'senderRules' => $senderRules,
            'senderRuleType' => $type,
            'title' => $title,
            'filter_form' => $filterForm->createView()
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
        $mainUser = $this->em->getRepository(User::class)->find($userId);
        $userAndAliases = [];

        // if address in an alias we get the target mail
        if ($mainUser && $mainUser->getOriginalUser()) {
            $mainUser = $mainUser->getOriginalUser();
        }

        // we check if aliases exist
        if ($mainUser) {
            $userAndAliases = $this->em->getRepository(User::class)->findBy(['originalUser' => $mainUser->getId()]);
            array_unshift($userAndAliases, $mainUser);
        }

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
    public function importSenderRuleAction(Request $request, string $type): Response
    {
        if ($type !== 'W' && $type !== 'B') {
            return new Response("Type has to be either `W` or `B`", 422);
        }
        $rule = $type === 'W' ? 'accept' : 'block';

        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('sender_rules_import', ['type' => $type]),
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/plain") {
                $filename = 'import-sender-rules-agentj-' . time() . ".txt";
                $file = $fileUpload->move('/tmp/', $filename);
                $this->importSenderRule($file->getPathname(), $form->get('domain')->getData(), $rule);
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

    /**
     * @param 'accept'|'block' $rule
     */
    private function importSenderRule(string $pathFile, Domain $domain, string $rule): void
    {
        $senderRules = [];
        if (($handle = fopen($pathFile, "r"))) {
            while (($data = fgets($handle, 4096)) !== false) {
                $data = $this->sanitizeImportedData($data);

                if ($data === null) {
                    continue;
                }

                $senderRuleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy(['email' => $data]);
                // if email doesn't exist then we create email in RuleAddress
                if (!$senderRuleAddress) {
                    $senderRuleAddress = new RuleAddress();
                    $senderRuleAddress->setEmail($data);
                    $senderRuleAddress->setPriority(6);
                    $this->em->persist($senderRuleAddress);
                    $this->em->flush();
                }

                if (
                    isset($senderRules[$domain->getId()]) &&
                    in_array($senderRuleAddress->getId(), $senderRules[$domain->getId()])
                ) {
                    continue;
                }

                $user = $this->em->getRepository(User::class)->findOneBy(['email' =>  '@' . $domain->getDomain()]);
                $senderRule = $this->em->getRepository(SenderRule::class)->findOneBy([
                    'senderRuleAddress' => $senderRuleAddress,
                    'user' => $user,
                ]);
                if (!$senderRule) {
                    $senderRule = new SenderRule($user, $senderRuleAddress);
                }

                $senderRule->setWbRule($rule);
                $senderRule->setPriority(SenderRule::PRIORITY_USER);
                $senderRule->setType(SenderRule::TYPE_IMPORT);
                $this->em->persist($senderRule);
                $senderRules[$domain->getId()][] = $senderRuleAddress->getId();
            }

            $this->em->flush();
        }
    }

    private function sanitizeImportedData(string $data): ?string
    {
        $data = trim($data);

        $email = filter_var($data, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
        if ($email !== false) {
            return $email;
        }

        // This allows domains to be imported in both formats: "example.org" and "@example.org".
        if (str_starts_with($data, '@')) {
            $data = substr($data, 1);
        }

        $domain = filter_var($data, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        if ($domain !== false) {
            return '@' . $domain;
        }

        return null;
    }
}
