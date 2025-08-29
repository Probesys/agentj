<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\DomainKey;
use App\Entity\Mailaddr;
use App\Entity\Settings;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\DomainMessageType;
use App\Form\DomainType;
use App\Model\ConnectorTypes;
use App\Repository\SettingsRepository;
use App\Service\FileUploader;
use App\Service\MailaddrService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Policy;

#[Route(path: '/domain')]
class DomainController extends AbstractController
{
    use ControllerWBListTrait;

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
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $domains = $this->em
                ->getRepository(Domain::class)
                ->findAll();
        } else {
            $domains = $user->getDomains();
        }


        return $this->render('domain/index.html.twig', ['domains' => $domains]);
    }

    #[Route(path: '/new', name: 'domain_new', methods: 'GET|POST')]
    public function new(
        Request $request,
        FileUploader $fileUploader,
        ParameterBagInterface $params,
        SettingsRepository $settingsRepository,
    ): Response {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            throw new AccessDeniedException();
        }

        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain, [
            'action' => $this->generateUrl('domain_new'),
            'actions' => $this->getWBListDomainActions(),
            'is_edit' => false,
            'minSpamLevel' => $this->getParameter('app.domain_min_spam_level'),
            'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);
        $form->get('defaultLang')->setData($params->get('locale'));

        // set normal policy anf "block all mails" as default rule for new domain
        $policy = $this->em->getRepository(Policy::class)->find(5);
        $form->get('policy')->setData($policy);
        $form->get('rules')->setData(0);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $strTransport = "smtp:[" . $domain->getSrvSmtp() . "]";
            if ($domain->getSmtpPort()) {
                $strTransport .= ":" . $domain->getSmtpPort();
            }
            $domain->setTransport($strTransport);
            //Default messages
            //captcha page
            $messageConfig = $settingsRepository->findBy(['context' => 'default_domain_messages']);
            if ($messageConfig) {
                $domain->setMessage($settingsRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'page_content_authentification_request',
                ])->getValue());
                $domain->setConfirmCaptchaMessage($settingsRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'page_content_authentification_valid',
                ])->getValue());
                $domain->setMailmessage($settingsRepository->findOneBy([
                    'context' => 'default_domain_messages',
                    'name' => 'mail_content_authentification_request',
                ])->getValue());
                $domain->setMessageAlert($settingsRepository->findOneBy([
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

            $rules = $form->get("rules")->getData();

            //for all domain @.
            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setPriority(0); // priority for domain is 0
                $mailaddr->setEmail('@.');
                $this->em->persist($mailaddr);
            }
            $wblist = new Wblist($user, $mailaddr);
            $wblist->setWb($rules);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);
            $this->em->persist($wblist);

            if ($form->has('logoFile')) {
                $uploadedFile = $form['logoFile']->getData();
                if ($uploadedFile) {
                    $uploadedLogo = $fileUploader->upload($uploadedFile);
                    $domain->setLogo($uploadedLogo);
                }
            }


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
            $form->get('level')->setData(0.5);
        }

        return $this->render('domain/new.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
            'domainSpamLevel' => $this->getParameter('app.domain_default_spam_level')
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'domain_edit', methods: 'GET|POST')]
    public function edit(Request $request, Domain $domain, FileUploader $fileUploader): Response
    {

        $this->checkAccess($domain);
        $form = $this->createForm(DomainType::class, $domain, [
            'action' => $this->generateUrl('domain_edit', ['id' => $domain->getId()]),
            'actions' => $this->getWBListDomainActions(),
            'is_edit' => true,
            'minSpamLevel' => $this->getParameter('app.domain_default_spam_level'),
            'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);

        $wblist = $this->em->getRepository(Wblist::class)->findOneByRecipientDomain($domain);

        if ($wblist === null) {
            throw $this->createNotFoundException('No wblist found for domain ' . $domain->getDomain());
        }

        $form->get('rules')->setData($wblist->getWb());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $strTransport = "smtp:[" . $domain->getSrvSmtp() . "]";
            if ($domain->getSmtpPort()) {
                $strTransport .= ":" . $domain->getSmtpPort();
            }
            $domain->setTransport($strTransport);

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

            $rules = $form->get("rules")->getData();
            $wblist->setWb($rules);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);

            // Update users with domain policy


            if ($form->has('logoFile')) {
                $uploadedFile = $form['logoFile']->getData();
                if ($uploadedFile) {
                    $uploadedLogo = $fileUploader->upload($uploadedFile);
                    $domain->setLogo($uploadedLogo);
                }
            }

            if ($domain->getDomainKeys() === null) {
                $this->generateOpenDkim($domain);
            }

            $em->persist($wblist);
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

    #[Route(path: '/{id}/delete', name: 'domain_delete', methods: 'GET')]
    public function delete(Request $request, Domain $domain): Response
    {
        $this->checkAccess($domain);
        if ($this->isCsrfTokenValid('delete' . $domain->getId(), $request->query->get('_token'))) {
            $em = $this->em;
            $em->remove($domain);
            $em->flush();
        }

        return $this->redirectToRoute('domain_index');
    }

    /** Lors de l'ajout d'une règle sur un domaine, on peut préciser pour l'expéditeur email ou d'un domaine
     */
    #[Route(path: '/{rid}/wblist/delete/{sid}', name: 'domain_wblist_delete', methods: 'GET|POST')]
    public function deleteWblist(int $rid, int $sid, Request $request): Response
    {
        $wbList = $this->em->getRepository(Wblist::class)->findOneBy(['rid' => $rid, 'sid' => $sid]);
        $domain = $wbList->getRid()->getDomain();
        $this->checkAccess($domain);
        if ($wbList) {
            $em = $this->em;
            $em->remove($wbList);
            $em->flush();
        }

        return $this->redirectToRoute('domain_index');
    }

    #[Route(path: '/{id}/wblist', name: 'domain_wblist', methods: 'GET')]
    public function domainwblist(Domain $domain): Response
    {
        $this->checkAccess($domain);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $wblist = $this->em->getRepository(Wblist::class)->findBy(['rid' => $user]);
        if (!$wblist) {
            $this->addFlash('danger', $this->translator->trans('Message.Flash.missingRuleForDomain'));
            return $this->redirectToRoute('domain_wblist_new', ['id' => $domain->getId(), 'type' => 'domain']);
        }
        return $this->render('domain/wblist.html.twig', ['domain' => $domain, 'wblist' => $wblist]);
    }

    /** Lors de l'ajout d'une règle sur un domaine, on peut préciser pour l'expéditeur email ou d'un domaine
     */
    #[Route(path: '/{id}/wblist/new', name: 'domain_wblist_new', methods: 'GET|POST')]
    public function newwblist(Domain $domain, Request $request, MailaddrService $mailaddrService): Response
    {
        $this->checkAccess($domain);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $formBuilder = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('domain_wblist_new', ['id' => $domain->getId()]),
        ]);
        $formBuilder->add('email', TextType::class);
        $actions = $this->getWBListUserActions();

        $formBuilder->add('wb', ChoiceType::class, [
            'choices' => $actions,
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $data['email']]));

            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail($data['email']);

                $priority = $mailaddrService->computePriority($data['email']);
                $mailaddr->setPriority($priority);

                $this->em->persist($mailaddr);
            } else {
                $domainWblistexist = $this->em->getRepository(Wblist::class)->findOneBy(([
                    'rid' => $user,
                    'sid' => $mailaddr,
                ]));
                if ($domainWblistexist) {
                    $this->addFlash('danger', $this->translator->trans('Message.Flash.ruleExistForDomain'));
                    return $this->redirectToRoute('domain_wblist', ['id' => $domain->getId()]);
                }
            }
            $wblist = new Wblist($user, $mailaddr);
            $wblist->setWb($data['wb']);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);

            $this->em->persist($wblist);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));
            return $this->redirectToRoute('domain_wblist', ['id' => $domain->getId()]);
        }

        return $this->render('domain/newwblist.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/messages', name: 'domain_messages', methods: 'GET|POST')]
    public function domainMessages(Request $request, Domain $domain): Response
    {
        $this->checkAccess($domain);
        $form = $this->createForm(DomainMessageType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('domain_index', ['id' => $domain->getId()]);
        }

        return $this->render('domain/messages.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
        ]);
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

            $this->addFlash('info', $domain->getSrvSmtp() . ' DKIM public key: ' . $dkim->getDnsEntry());
            return true;
        } catch (ProcessFailedException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return false;
        }
    }
}
