<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\Mailaddr;
use App\Entity\Settings;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\DomainMessageType;
use App\Form\DomainType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/domain")
 */
class DomainController extends AbstractController
{
    use ControllerWBListTrait;

    private $translator;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em) {
//        parent::__construct();
        $this->translator = $translator;
        $this->em = $em;
    }
    

    private function checkAccess($domain)
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $allowedomains = $this->em
              ->getRepository(Domain::class)
              ->getListByUserId($this->getUser()->getId());
            if (!in_array($domain, $allowedomains)) {
                throw new AccessDeniedException();
            }
        }
    }

  /**
   * @Route("/", name="domain_index", methods="GET")
   */
    public function index(): Response
    {

        if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $domains = $this->em
              ->getRepository(Domain::class)
              ->findAll();
        } else {
            $domains = $this->em
              ->getRepository(Domain::class)
              ->getListByUserId($this->getUser()->getId());
        }


        return $this->render('domain/index.html.twig', ['domains' => $domains]);
    }

  /**
   * @Route("/new", name="domain_new", methods="GET|POST")
   */
    public function new(Request $request, FileUploader $fileUploader, ParameterBagInterface $params): Response
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            throw new AccessDeniedException();
        }
//        dd($this->getWBListDomainActions());
        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain, [
        'action' => $this->generateUrl('domain_new'),
        'actions' => $this->getWBListDomainActions(),
        'minSpamLevel' => $this->getParameter('app.domain_min_spam_level'),
        'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);
        $form->get('defaultLang')->setData($params->get('locale'));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $strTransport = "smtp:[" . $domain->getSrvSmtp() . "]";
            if ($domain->getSmtpPort()){
                $strTransport .= ":" . $domain->getSmtpPort();
            }
            $domain->setTransport($strTransport);
          //Default messages
          //captcha page
            $messageConfig = $this->em->getRepository(Settings::class)->findBy(['context' => 'default_domain_messages']);
            if ($messageConfig) {
                $domain->setMessage($this->em->getRepository(Settings::class)->findOneBy(['context' => 'default_domain_messages', 'name' => 'page_content_authentification_request'])->getValue());
                $domain->setConfirmCaptchaMessage($this->em->getRepository(Settings::class)->findOneBy(['context' => 'default_domain_messages', 'name' => 'page_content_authentification_valid'])->getValue());
                $domain->setMailmessage($this->em->getRepository(Settings::class)->findOneBy(['context' => 'default_domain_messages', 'name' => 'mail_content_authentification_request'])->getValue());
                $domain->setMessageAlert($this->em->getRepository(Settings::class)->findOneBy(['context' => 'default_domain_messages', 'name' => 'mail_content_report'])->getValue());
            }






            $em = $this->em;
            $em->persist($domain);

          //add domain to users
            $user = new User();
            $user->setEmail('@' . $domain->getDomain());
            $user->setFullname('Domaine ' . $domain->getDomain());
            $user->setDomain($domain);
            $user->setPriority(2);
            $user->setPolicy($domain->getPolicy());
            $em->persist($user);

            $rules = $form->get("rules")->getData();

          //for all domain @.
            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => '@.']));
            if (!$mailaddr) {
                $mailaddr = new Mailaddr();
                $mailaddr->setPriority(0); // priority for domain is 0
                $mailaddr->setEmail('@.');
                $em->persist($mailaddr);
            }
            $wblist = new Wblist($user, $mailaddr);
            $wblist->setWb($rules);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);
            $em->persist($wblist);

            if ($form->has('logoFile')) {
                $uploadedFile = $form['logoFile']->getData();
                if ($uploadedFile) {
                    $uploadedLogo = $fileUploader->upload($uploadedFile);
                    $domain->setLogo($uploadedLogo);
                }
            }

            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.domainCreatd'));
            return $this->redirectToRoute('domain_index');
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $cause = $error->getCause();
                $this->addFlash('error', $this->translator->trans($cause->getMessage() . ' : ' . $cause->getInvalidValue()));
            }

            return $this->redirectToRoute('domain_index');
        } else {
            $form->get('level')->setData(0.5);
        }

        return $this->render('domain/new.html.twig', [
                'domain' => $domain,
                'form' => $form->createView(),
                'domainSpamLevel' => $this->getParameter('app.domain_default_spam_level')
        ]);
    }

  /**
   * @Route("/{id}", name="domain_show", methods="GET")
   */
    public function show(Domain $domain): Response
    {
        return $this->render('domain/show.html.twig', ['domain' => $domain]);
    }

  /**
   * @Route("/{id}/edit", name="domain_edit", methods="GET|POST")
   */
    public function edit(Request $request, Domain $domain, FileUploader $fileUploader): Response
    {

        $this->checkAccess($domain);
        $form = $this->createForm(DomainType::class, $domain, [
        'action' => $this->generateUrl('domain_edit', ['id' => $domain->getId()]),
        'actions' => $this->wBListDomainActions,
        'minSpamLevel' => $this->getParameter('app.domain_default_spam_level'),
        'maxSpamLevel' => $this->getParameter('app.domain_max_spam_level'),
        ]);
        $wblistArray = $this->em->getRepository(Wblist::class)->searchByReceiptDomain('@' . $form->getData('domain')->getDomain());
        $form->get('rules')->setData($wblistArray['wb']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            
            $strTransport = "smtp:[" . $domain->getSrvSmtp() . "]";
            if ($domain->getSmtpPort()){
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
            $wblist = $this->em->getRepository(Wblist::class)->findOneBy(['rid' => $wblistArray['rid'], 'sid' => $wblistArray['sid']]);
            $wblist->setWb($rules);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);

            if ($form->has('logoFile')) {
                $uploadedFile = $form['logoFile']->getData();
                if ($uploadedFile) {
                    $uploadedLogo = $fileUploader->upload($uploadedFile);
                    $domain->setLogo($uploadedLogo);
                }
            }


            $em->persist($wblist);
            $em->persist($userDomain);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.domainUpdated'));
            return $this->redirectToRoute('domain_index', ['id' => $domain->getId()]);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $cause = $error->getCause();
                $this->addFlash('error', $this->translator->trans($cause->getMessage() . ' : ' . $cause->getInvalidValue()));
            }

            return $this->redirectToRoute('domain_index');
        }

        return $this->render('domain/edit.html.twig', [
                'domain' => $domain,
                'form' => $form->createView(),
                'domainSpamLevel' => $domain->getLevel()
        ]);
    }

  /**
   * @Route("/{id}/delete", name="domain_delete", methods="GET")
   */
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
   * @Route("/{rid}/wblist/delete/{sid}", name="domain_wblist_delete", methods="GET|POST")
   */
    public function deleteWblist($rid, $sid, Request $request): Response
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

  /**
   * @Route("/{id}/wblist", name="domain_wblist", methods="GET")
   */
    public function domainwblist(Domain $domain): Response
    {
        $this->checkAccess($domain);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $wblist = $this->em->getRepository(Wblist::class)->findBy(['rid' => $user]);
        if (!$wblist) {
            $this->addFlash('danger', $this->translator->trans('Message.Flash.missingRuleForDomain'));
            return $this->redirectToRoute('domain_wblist_new', ['domainId' => $domain->getId(), 'type' => 'domain']);
        }
        return $this->render('domain/wblist.html.twig', ['domain' => $domain, 'wblist' => $wblist]);
    }

  /** Lors de l'ajout d'une règle sur un domaine, on peut préciser pour l'expéditeur email ou d'un domaine
   * @Route("/{domainId}/wblist/new", name="domain_wblist_new", methods="GET|POST")
   */
    public function newwblist($domainId, Request $request, GroupsController $groupsController, AuthorizationCheckerInterface $authChecker): Response
    {
        $em = $this->em;
        $domain = $this->em->getRepository(Domain::class)->find($domainId);
        if (!$domain) {
            throw $this->createNotFoundException('The domain does not exist');
        }
        $this->checkAccess($domain);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domain->getDomain()]);
        $formBuilder = $this->createFormBuilder(null, [
        'action' => $this->generateUrl('domain_wblist_new', ['domainId' => $domainId]),
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

                $priority = $this->computeMailAddrPriority($data['email']);
                $mailaddr->setPriority($priority);

                $em->persist($mailaddr);
            } else {
                $domainWblistexist = $this->em->getRepository(Wblist::class)->findOneBy((['rid' => $user, 'sid' => $mailaddr]));
                if ($domainWblistexist) {
                    $this->addFlash('danger', $this->translator->trans('Message.Flash.ruleExistForDomain'));
                    return $this->redirectToRoute('domain_wblist', ['id' => $domainId]);
                }
            }
            $wblist = new Wblist($user, $mailaddr);
            $wblist->setWb($data['wb']);
            $wblist->setPriority(Wblist::WBLIST_PRIORITY_DOMAIN);

            $em->persist($wblist);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));
            return $this->redirectToRoute('domain_wblist', ['id' => $domainId]);
        }

        return $this->render('domain/newwblist.html.twig', [
                'domain' => $domain,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/{id}/messages", name="domain_messages", methods="GET|POST")
   */
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

  /**
   *
   * @param type $email
   * @return int
   */
    private function computeMailAddrPriority($email = "")
    {

        $priority = 5; //default priority for email
      //in case domain
        if (substr(trim($email), 0, 1) == '@') {
            $domain = substr($email, 1);
            if ($domain == '.') {
                $priority = 0; //in case @.
            }
            $subdomain = explode('.', $domain);
            if (count($subdomain) == "2") {
                if ($subdomain[0] == '') {//in case @.com
                    $priority = 1;
                } else {//in case @example.com
                    $priority = 5; // to be confirme
                }
            } elseif (count($subdomain) == "3") {
                if ($subdomain[0] == '') {//in case @.example.com
                    $priority = 2;
                } else {//in case @sub.example.com
                    $priority = 5;
                }
            } elseif (count($subdomain) == "4") {
                if ($subdomain[0] == '') {//in case @.sub.example.com
                    $priority = 3;
                }
            }
        } else {
          //todo user (priority 6) / user+foo (priority 7) / user@sub.example.com (priority 8) / user+foo@sub.example.com (priority 9)
        }
        return $priority;
    }
}
