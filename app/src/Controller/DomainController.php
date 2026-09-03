<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\DomainKey;
use App\Entity\Policy;
use App\Entity\RuleAddress;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Form\DomainType;
use App\Model\ConnectorTypes;
use App\Repository\DomainRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\SettingRepository;
use App\Service\RuleAddressService;
use App\Service\UserService;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/domain')]
class DomainController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        private UserService $userService,
    ) {
    }

    private function checkAccess(Domain $domain): void
    {
        /** @var User $user */
        $user = $this->getUser();
        if (
            !in_array('ROLE_SUPER_ADMIN', $user->getRoles()) &&
            !$user->hasDomain($domain)
        ) {
            throw new AccessDeniedException();
        }
    }

    #[Route(path: '/', name: 'domain_index', methods: 'GET')]
    public function index(
        Request $request,
        DomainRepository $domainRepository,
        PaginatorInterface $paginator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $domainsQuery = $domainRepository->getSearchQuery(
            currentUser: $user,
            searchKey: $request->query->getString('search')
        );

        $this->sanitizeSortParameter($request);

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $domains = $paginator->paginate(
            $domainsQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'd.domain',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render(
            'domain/index.html.twig',
            [
                'domains' => $domains,
            ]
        );
    }

    #[Route(path: '/new', name: 'domain_new', methods: 'GET|POST')]
    public function new(
        Request $request,
        ParameterBagInterface $params,
        SettingRepository $settingRepository,
    ): Response {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            throw new AccessDeniedException();
        }

        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain, [
            'action' => $this->generateUrl('domain_new'),
            'is_edit' => false,
            'minSpamLevel' => $this->getParameter('app.domain_min_spam_level'),
            'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);

        // set normal policy and "enabled" as default rule for new domain
        $policy = $this->em->getRepository(Policy::class)->find(5);
        $form->get('policy')->setData($policy);
        $form->get('wbRule')->setData('enabled');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domain->setCalculatedTransport();

            //Default messages
            //captcha page
            $messageConfig = $settingRepository->findBy(['context' => 'default_domain_messages']);
            if ($messageConfig) {
                $domain->setMessage($settingRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'page_content_authentification_request',
                ])->getValue());
                $domain->setConfirmCaptchaMessage($settingRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'page_content_authentification_valid',
                ])->getValue());
                $domain->setMailmessage($settingRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'mail_content_authentification_request',
                ])->getValue());
                $domain->setMessageAlert($settingRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'mail_content_report',
                ])->getValue());
            }

            $this->generateOpenDkim($domain);

            $this->em->persist($domain);

            //add domain to users
            $user = new User();
            $user->setEmail('@' . $domain->getDomain());
            $user->setFullname('Domaine ' . $domain->getDomain());
            $user->setDomain($domain);
            $user->setPriority(2);
            $user->setPolicy($domain->getPolicy());
            $this->em->persist($user);

            $wbRule = $form->get("wbRule")->getData();

            //for all domain @.
            $ruleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy((['email' => '@.']));
            if (!$ruleAddress) {
                $ruleAddress = new RuleAddress();
                $ruleAddress->setPriority(0); // priority for domain is 0
                $ruleAddress->setEmail('@.');
                $this->em->persist($ruleAddress);
            }
            $senderRule = new SenderRule($user, $ruleAddress);
            $senderRule->setWbRule($wbRule);
            $senderRule->setPriority(SenderRule::PRIORITY_DOMAIN);
            $this->em->persist($senderRule);

            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.domainCreatd'));
            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()]);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $cause = $error->getCause();
                $this->addFlash(
                    'error',
                    $this->translator->trans($cause->getMessage() . ' : ' . $cause->getInvalidValue())
                );
            }

            return $this->redirectToRoute('domain_new');
        } else {
            $defaultSpamLevel = $this->getParameter('app.domain_default_spam_level');
            $form->get('level')->setData($defaultSpamLevel);
        }

        return $this->render('domain/new.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'domain_edit', methods: 'GET|POST')]
    public function edit(Request $request, Domain $domain): Response
    {

        $this->checkAccess($domain);
        $form = $this->createForm(DomainType::class, $domain, [
            'action' => $this->generateUrl('domain_edit', ['id' => $domain->getId()]),
            'is_edit' => true,
            'minSpamLevel' => $this->getParameter('app.domain_default_spam_level'),
            'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);

        $senderRule = $this->em->getRepository(SenderRule::class)->findOneByRecipientDomain($domain);

        if ($senderRule === null) {
            throw $this->createNotFoundException('No sender rule found for domain ' . $domain->getDomain());
        }

        $form->get('wbRule')->setData($senderRule->getWbRule());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $domain->setCalculatedTransport();

            $policy = $form->get('policy')->getData();
            $userDomain = $this->em->getRepository(User::class)->findOneBy((['email' => '@' . $domain->getDomain()]));
            if (!$userDomain) {
                $userDomain = new User();
                $userDomain->setEmail('@' . $domain->getDomain());
                $userDomain->setFullname("Domain " . $domain->getDomain());
                $userDomain->setPriority(2);
                $userDomain->setDomain($domain);
            }
            $userDomain->setPolicy($policy);

            $wbRule = $form->get("wbRule")->getData();
            $senderRule->setWbRule($wbRule);
            $senderRule->setPriority(SenderRule::PRIORITY_DOMAIN);

            if ($domain->getDomainKeys() === null) {
                $this->generateOpenDkim($domain);
            }

            $em->persist($senderRule);
            $em->persist($userDomain);
            $em->flush();

            $this->userService->updateUsersPolicyfromDomain($domain);

            $this->addFlash('success', $this->translator->trans('Message.Flash.domainUpdated'));
            return $this->redirectToRoute('domain_index', ['id' => $domain->getId()]);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $cause = $error->getCause();
                $this->addFlash(
                    'error',
                    $this->translator->trans($cause->getMessage() . ' : ' . $cause->getInvalidValue()),
                );
            }

            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()]);
        }

        $dkim = $domain->getDomainKeys();
        $dnsInfo = $dkim?->getDnsinfo();
        return $this->render('domain/edit.html.twig', [
            'domain' => $domain,
            'dkim' => $dkim,
            'dnsInfo' => $dnsInfo,
            'connectorTypes' => ConnectorTypes::all(),
            'form' => $form->createView(),
            'domainSpamLevel' => $domain->getLevel()
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'domain_delete', methods: 'POST')]
    public function delete(Request $request, Domain $domain): Response
    {
        $this->checkAccess($domain);
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $domain->getId(), $token)) {
            $em = $this->em;
            $em->remove($domain);
            $em->flush();
        }

        return $this->redirectToRoute('domain_index');
    }

    #[Route(path: '/{id}/rules', name: 'domain_sender_rules_index', methods: 'GET')]
    public function domainSenderRule(
        Domain $domain,
        Request $request,
        PaginatorInterface $paginator,
        SenderRuleRepository $senderRuleRepository
    ): Response {
        $this->checkAccess($domain);

        $userDomain = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);

        $searchKey = $request->query->getString('search', '');

        $senderRulesQuery = $senderRuleRepository->getDomainSearchQuery(
            domainUser: $userDomain,
            searchKey: $searchKey
        );

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $senderRules = $paginator->paginate(
            $senderRulesQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'sender.email',
                'defaultSortDirection' => 'asc',
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
            ]
        );


        return $this->render('domain/sender_rule/index.html.twig', [
            'domain' => $domain,
            'senderRules' => $senderRules,
        ]);
    }

    #[Route(path: '/{id}/rules/new', name: 'domain_sender_rules_new', methods: 'GET|POST')]
    public function newSenderRule(Domain $domain, Request $request, RuleAddressService $ruleAddressService): Response
    {
        $this->checkAccess($domain);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $formBuilder = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('domain_sender_rules_new', ['id' => $domain->getId()]),
        ]);
        $formBuilder->add('email', TextType::class);

        $formBuilder->add('wbRule', ChoiceType::class, [
            'choices' => ['accept', 'block', 'allow'],
            'choice_label' => function (string $choice): TranslatableMessage {
                return new TranslatableMessage("Entities.SenderRule.rules.{$choice}");
            },
            'label' => new TranslatableMessage('Entities.SenderRule.fields.wbRule'),
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = Email::normalize($data['email']);

            $ruleAddress = $this->em->getRepository(RuleAddress::class)->findOneBy([
                'email' => $email,
            ]);

            if (!$ruleAddress) {
                $ruleAddress = new RuleAddress();
                $ruleAddress->setEmail($email);

                $priority = $ruleAddressService->computePriority($email);
                $ruleAddress->setPriority($priority);

                $this->em->persist($ruleAddress);
            } else {
                $domainSenderRuleExists = $this->em->getRepository(SenderRule::class)->findOneBy(([
                    'user' => $user,
                    'senderRuleAddress' => $ruleAddress,
                ]));
                if ($domainSenderRuleExists) {
                    $this->addFlash('danger', $this->translator->trans('Message.Flash.ruleExistForDomain'));
                    return $this->redirectToRoute('domain_sender_rules_index', ['id' => $domain->getId()]);
                }
            }
            $senderRule = new SenderRule($user, $ruleAddress);
            $senderRule->setWbRule($data['wbRule']);
            $senderRule->setPriority(SenderRule::PRIORITY_DOMAIN);

            $this->em->persist($senderRule);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));
            return $this->redirectToRoute('domain_sender_rules_index', ['id' => $domain->getId()]);
        }

        return $this->render('domain/sender_rule/new.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{rid}/rules/{sid}/delete', name: 'domain_sender_rules_delete', methods: 'POST')]
    public function deleteSenderRule(
        int $rid,
        int $sid,
        Request $request,
        SenderRuleRepository $senderRuleRepository
    ): Response {
        $senderRule = $senderRuleRepository->findOneBy(['user' => $rid, 'senderRuleAddress' => $sid]);
        $domain = $senderRule->getUser()->getDomain();
        $this->checkAccess($domain);

        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete', $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('domain_sender_rules_index', ['id' => $domain->getId()]);
        }

        $this->em->remove($senderRule);
        $this->em->flush();

        return $this->redirectToRoute('domain_sender_rules_index', ['id' => $domain->getId()]);
    }

    /* Generate private and public keys for DKIM */
    private function generateOpenDkim(Domain $domain): bool
    {
        $dkim = $domain->getDomainKeys();
        if ($dkim === null) {
            $dkim = new DomainKey();
            $domain->setDomainKeys($dkim);
        }
        $dkim->setDomainName($domain->getDomain());
        $dkim->setSelector('agentj');
        try {
            $privateKey = openssl_pkey_new();
            if ($privateKey === false) {
                $this->addFlash('error', $this->translator->trans('Message.Flash.failedToGeneratePrivateKey'));
                return false;
            }
            $privkeyPem = null;
            openssl_pkey_export($privateKey, $privkeyPem);
            $dkim->setPrivateKey($privkeyPem);

            $details = openssl_pkey_get_details($privateKey);

            if ($details === false) {
                $this->addFlash('error', $this->translator->trans('Message.Flash.failedToGeneratePublicKey'));
                return false;
            }

            $pubkeyPem = $details['key'];
            $dkim->setPublicKey($pubkeyPem);
            // $public_key = openssl_pkey_get_public($public_key_pem);

            return true;
        } catch (ProcessFailedException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return false;
        }
    }


    private function sanitizeSortParameter(Request $request): void
    {
        $allowedSortFields = [
            'd.domain',
            'd.datemod',
            'd.active',
        ];

        $sort = $request->query->getString('sort', 'd.domain');

        if (!in_array($sort, $allowedSortFields, true)) {
            $request->query->set('sort', 'd.domain');
        }
    }
}
