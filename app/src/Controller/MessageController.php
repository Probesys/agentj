<?php

namespace App\Controller;

use App\Model\ValidationSource;
use App\Entity\Mailaddr;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\Quarantine;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\ActionsFilterType;
use App\Repository\MsgsRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use eXorus\PhpMimeMailParser\Exception;
use eXorus\PhpMimeMailParser\Parser;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webklex\PHPIMAP\Message;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: '/message')]
class MessageController extends AbstractController
{

    private $translator;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

    #[Route(path: '/{type}', name: 'message')]
    public function index(Request $request, TranslatorInterface $translator, PaginatorInterface $paginator, $type = null)
    {
        $subTitle = '';
        $messageActions = [
            'Message.Actions.Delete' => 'delete'
        ];

        if ($type) {
            switch ($type) {
                case 'spam':
                    $subTitle = 'Entities.Message.spammedDetect';
                    $messageActions = [
                        'Message.Actions.Restore' => 'restore',
                        'Message.Actions.Autorized' => 'authorized',
                        'Message.Actions.Banned' => 'banned',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    $type = MessageStatus::SPAMMED;
                    break;
                case 'virus':
                    $subTitle = 'Entities.Message.virus';
                    $type = MessageStatus::VIRUS;
                    $messageActions = [];
                    break;
                case 'banned':
                    $subTitle = 'Entities.Message.' . $type;
                    $type = MessageStatus::BANNED;
                    $messageActions = [
                        'Message.Actions.Autorized' => 'authorized',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    break;
                case 'authorized':
                    $subTitle = 'Entities.Message.' . $type;
                    $messageActions = [
                        'Message.Actions.Banned' => 'banned',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    $type = MessageStatus::AUTHORIZED;
                    break;
                case 'delete':
                    $subTitle = 'Entities.Message.' . $type;
                    $type = MessageStatus::DELETED;
                    break;
                case 'restored':
                    $subTitle = 'Entities.Message.' . $type;
                    $type = MessageStatus::RESTORED;
                    break;

                default:
                    $subTitle = 'Entities.Message.untreated';
                    $messageActions = [
                        'Message.Actions.Autorized' => 'authorized',
                        'Message.Actions.Banned' => 'banned',
                        'Message.Actions.Delete' => 'delete',
                        'Message.Actions.Restore' => 'restore',
                    ];
                    $type = MessageStatus::UNTREATED;
                    break;
            }
        } else {
            $messageActions = [
                'Message.Actions.Autorized' => 'authorized',
                'Message.Actions.Banned' => 'banned',
                'Message.Actions.Delete' => 'delete',
                'Message.Actions.Restore' => 'restore',
            ];
            $subTitle = 'Entities.Message.untreated';
        }
        $email = "";

        $filterForm = $this->createForm(ActionsFilterType::class, null, ['avalaibleActions' => $messageActions, 'action' => $this->generateUrl('message_batch')]);

        if ($type == MessageStatus::DELETED || $type == MessageStatus::VIRUS) {
            $filterForm->remove('actions');
        }

        if ($this->getUser()->getEmail() && in_array('ROLE_USER', $this->getUser()->getRoles())) {
            $email = stream_get_contents($this->getUser()->getEmail(), -1, 0);
        }
        $em = $this->em;
        $alias = $em->getRepository(User::class)->findBy(['originalUser' => $this->getUser()->getId()]);

        $sortParams = [];
        if ($request->query->has('sortDirection') && $request->query->has('sortField')) {
            $sortParams['sort'] = $request->query->get('sortField');
            $sortParams['direction'] = $request->query->get('sortDirection');
        }
        $searchKey = null;
        if ($request->query->has('search')) {
            $searchKey = trim($request->query->get('search'));
        }

        $allMessages = $em->getRepository(Msgs::class)->search($this->getUser(), $type, $alias, $searchKey, $sortParams, null);
        $totalMsgFound = count($allMessages);

        $per_page = (int) $this->getParameter('app.per_page_global');
        if ($request->query->has('per_page') && $request->query->getInt('per_page') > 0) {
            $per_page = $request->query->getInt('per_page', $per_page);
            $request->getSession()->set('per_page', $per_page);
        } elseif ($request->getSession()->has('per_page') && $request->getSession()->get('per_page') > 0) {
            $per_page = $request->getSession()->get('per_page');
        }
        $filterForm->get('per_page')->setData($per_page);

        $msg2Show = $paginator->paginate(
            $allMessages,
            $request->query->getInt('page', 1)/* page number */,
            $per_page
        );
        unset($allMessages);
        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
            'subTitle' => $subTitle,
            'msgs' => $msg2Show,
            'type' => $type,
            'per_page_global' => $per_page,
            'totalItemFound' => $totalMsgFound,
            'filter_form' => $filterForm->createView()
        ]);
    }

    /**
     * Show infos about message.
     * @param integer $id
     *
     * @return Response
     */
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/show/', name: 'message_show', methods: 'GET')]
    public function showAction($partitionTag, $mailId, $rid, Request $request)
    {
        $em = $this->em;

        /* @var $msgs Msgrcpt */
        $msgRcpt = $em->getRepository(Msgrcpt::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid
        ]);
        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }
        $this->checkMailAccess($msgRcpt);

        /* @var $msg Msgs */
        $msg = $em->getRepository(Msgs::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
        ]);
        /* @var $user User */
        $wbListInfos = $em->getRepository(Wblist::class)->getWbListInfoForSender($msg->getSid()->getEmailClear(), $msgRcpt->getRid()->getEmailClear(), $msg->getTimeIso());
        return $this->render('message/show.html.twig', [
            'controller_name' => 'MessageController',
            'msg' => $msg,
            'msgRcpt' => $msgRcpt,
            'wblistInfo' => $wbListInfos
        ]);
    }

    /**
     * Delete a message entity.
     * @param integer $id
     *
     * @return Response
     */
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/delete/', name: 'message_delete', methods: 'GET')]
    public function deleteAction($partitionTag, $mailId, $rid, Request $request)
    {
        $em = $this->em;
        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);
        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }
        $this->checkMailAccess($msgRcpt);
        //    $em->getRepository(Msgs::class)->changeStatus($partitionTag, $mailId, MessageStatus::DELETED); //status == delete
        $em->getRepository(Msgrcpt::class)->changeStatus($partitionTag, $mailId, MessageStatus::DELETED, $rid); //status == delete
        $this->addFlash('success', $this->translator->trans('Message.Flash.messageDeleted'));
        $logService = new LogService($em);
        $logService->addLog('delete', $mailId);
        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    /**
     *
     * @return Response
     */
    #[Route(path: '/batch/{action}', name: 'message_batch', methods: 'POST', options: ['expose' => true])]
    public function batchMessageAction(Request $request, MsgsRepository $msgRepository, $action = null)
    {
        $em = $this->em;
        if ($action) {
            foreach ($request->request->all('id') as $obj) {
                $mailInfo = json_decode($obj);
                switch ($action) {
                    case 'authorized':
                        $this->msgsToWblist($mailInfo[0], $mailInfo[1], "W", MessageStatus::AUTHORIZED, 0, $mailInfo[2]);
                        $logService = new LogService($em);
                        $logService->addLog('autho  rized batch', $mailInfo[1]);
                        break;
                    case 'banned':
                        $this->msgsToWblist($mailInfo[0], $mailInfo[1], "B", MessageStatus::BANNED, 0, $mailInfo[2]);
                        $logService = new LogService($em);
                        $logService->addLog('banned batch', $mailInfo[1]);
                        break;
                    case 'delete':
                        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $mailInfo[0], 'mailId' => $mailInfo[1], 'rid' => $mailInfo[2]]);
                        $this->checkMailAccess($msgRcpt);
                        $em->getRepository(Msgs::class)->changeStatus($mailInfo[0], $mailInfo[1], MessageStatus::DELETED);
                        $em->getRepository(Msgrcpt::class)->changeStatus($mailInfo[0], $mailInfo[1], MessageStatus::DELETED, $mailInfo[2]);
                        $logService = new LogService($em);
                        $logService->addLog('delete batch', $mailInfo[1]);
                        break;
                    case 'restore':
                        $this->restore($mailInfo[0], $mailInfo[1], $mailInfo[2], $request);
                        $logService = new LogService($em);
                        $logService->addLog('restore batch', $mailInfo[1]);
                        break;
                }
            }
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/authorized', name: 'message_authorized')]
    public function authorized($partitionTag, $mailId, $rid, Request $request)
    {
        $em = $this->em;
        //W = White and 2 is authorized
        if ($this->msgsToWblist($partitionTag, $mailId, "W", MessageStatus::AUTHORIZED, 0, $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderAuthorized'));
            $logService = new LogService($em);
            $logService->addLog('authorized', $mailId);
        }
        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/banned', name: 'message_banned')]
    public function banned($partitionTag, $mailId, $rid, Request $request)
    {

        //B = Black and msg status = 1 is banned
        if ($this->msgsToWblist($partitionTag, $mailId, "B", MessageStatus::BANNED, 0, $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderBanned'));
            $logService = new LogService($this->em);
            $logService->addLog('banned', $mailId);
        }
        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    /**
     * Only release the message for all recipients. Does not add entries in wblist
     * @param type $partitionTag
     * @param type $mailId
     * @param type $rid
     * @return RedirectResponse
     * @throws ProcessFailedException
     */
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/restore', name: 'message_restore')]
    public function restore($partitionTag, $mailId, $rid, Request $request)
    {
        //select msgs and msgcpt
        $msgs = $this->em->getRepository(Msgs::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);

        /* @var $msgrcpt Msgrcpt */
        $msgrcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);

        if ($msgrcpt && $msgs->getQuarLoc() && $msgs->getSecretId()) {
            $this->checkMailAccess($msgrcpt);
            $mailRcpt = stream_get_contents($msgrcpt->getRid()->getEmail(), -1, 0);
            $process = new Process([$this->getParameter('app.amavisd-release'), stream_get_contents($msgs->getQuarLoc(), -1, 0), stream_get_contents($msgs->getSecretId(), -1, 0), $mailRcpt]);
            $process->run(
                function ($type, $buffer) use ($msgrcpt) {
                    $msgrcpt->setAmavisOutput($buffer);
                }
            );
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $this->addFlash('success', $this->translator->trans('Message.Flash.messageRestored'));
            //      $logService = new LogService($em);
            //      $logService->addLog('restored', $mailId);

            $msgStatus = $this->em->getRepository(MessageStatus::class)->find(MessageStatus::RESTORED);
            $msgs->setStatus($msgStatus);
            $this->em->persist($msgs);

            $msgrcpt->setStatus($msgStatus);
            $this->em->persist($msgrcpt);

            $this->em->flush();

            $logService = new LogService($this->em);
            $logService->addLog('restore', $mailId);
        }
        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    /**
     * Create wblist and change the message status and type (0 = user validate / 1 = captcha validate / 2 = group validate )
     * @param type $partitionTag
     * @param type $mailId
     * @param type $wb
     * @param type $status
     * @param ValidationSource|int $type
     * @param type $rid
     * @return boolean
     * @throws type
     * @throws ProcessFailedException
     */
    public function msgsToWblist($partitionTag, $mailId, $wb, $status, ValidationSource|int $type = ValidationSource::user, $rid = null)
    {
        $state = false;
        $em = $this->em;

        //select msgs and msgcpt
        /* @var $msgs Msgs */
        $msgs = $em->getRepository(Msgs::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);
        if (!$msgs) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        if ($rid) {
            $msgrcpt = $em->getRepository(Msgrcpt::class)->findBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);
        } else {
            $msgrcpt = $em->getRepository(Msgrcpt::class)->findBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);
        }

        if (!$msgrcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        //check if sender exists in the database
        $emailSender = stream_get_contents($msgs->getSid()->getEmail(), -1, 0);
        //check from_addr email. If it's different from $mailaddrSender we will use it from wblist

        $fromAddr = null;
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $msgs->getFromAddr(), $matches);
        if (isset($matches[0][0]) && filter_var($matches[0][0], FILTER_VALIDATE_EMAIL)) {
            $fromAddr = filter_var($matches[0][0], FILTER_VALIDATE_EMAIL);
        }

        $emailSenderToWb = ($fromAddr && $emailSender != $fromAddr) ? $fromAddr : $emailSender;

        $mailaddrSender = $em->getRepository(Mailaddr::class)->findOneBy(['email' => $emailSenderToWb]);

        //if not we create email in Mailaddr
        if (!$mailaddrSender) {
            $mailaddrSender = new Mailaddr();
            $mailaddrSender->setEmail($emailSenderToWb);
            $mailaddrSender->setPriority(6);
            $em->persist($mailaddrSender);
        }

        $userAndAliases = [];
        foreach ($msgrcpt as $messageRecipient) {
	    if ($type !== ValidationSource::captcha)
	        $this->checkMailAccess($messageRecipient);

            $emailReceipt = stream_get_contents($messageRecipient->getRid()->getEmail(), -1, 0);
            $mainUser = $em->getRepository(User::class)->findOneBy(['email' => $emailReceipt]);

            // if adress in an alias we get the target mail
            if ($mainUser && $mainUser->getOriginalUser()) {
                $mainUser = $mainUser->getOriginalUser();
            }

            // we check if aliases exist
            if ($mainUser) {
                $userAndAliases = $em->getRepository(User::class)->findBy(['originalUser' => $mainUser->getId()]);
                array_unshift($userAndAliases, $mainUser);
            }

            foreach ($userAndAliases as $user) {
                //add white liste
                //        $sid = $mailaddrSender->getId();
                $rid = $user->getId();
                //if the msgs is authorized we release all msgs of the same sender
                //create Wblist with value White and user (User Object) and mailSender (Mailaddr Object)
                $wblist = $em->getRepository(Wblist::class)->findOneBy(['sid' => $mailaddrSender, 'rid' => $user]);
                if (!$wblist) {
                    $wblist = new Wblist($user, $mailaddrSender);
                }
                $wblist->setWb($wb);
                $wblist->setType($type);
                $wblist->setPriority(Wblist::WBLIST_PRIORITY_USER);
                $em->persist($wblist);

                $messageStatus = $em->getRepository(MessageStatus::class)->find($status);

                //get all msgs from email Sender and send all message for one user
                $msgsRelease = $em->getRepository(Msgs::class)->getAllMessageRecipient($emailSender, $user);
                foreach ($msgsRelease as $msgRelease) {
                    if (isset($msgRelease['quar_loc']) && isset($msgRelease['secret_id'])) {
                        /* @var $oneMsgRcpt Msgrcpt */
                        $oneMsgRcpt = $em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $msgRelease['partition_tag'], 'mailId' => $msgRelease['mail_id'], 'rid' => $msgRelease['rid']]);
                        //if W we deliver blokec messages if it has been released yet
                        if ($wb == 'W' && !$oneMsgRcpt->getAmavisOutput()) {
                            $process = new Process([$this->getParameter('app.amavisd-release'), $msgRelease['quar_loc'], $msgRelease['secret_id'], $msgRelease['recept_mail']]);
                            $process->getCommandLine();
                            $process->run(
                                function ($type, $buffer) use ($oneMsgRcpt) {
                                    $oneMsgRcpt->setAmavisOutput($buffer);
                                }
                            );
                            if (!$process->isSuccessful()) {
                                throw new ProcessFailedException($process);
                            }
                        }

                        $oneMsgRcpt->setStatus($messageStatus);
                        $em->persist($oneMsgRcpt);
                        $em->flush();

                        $logService = new LogService($em);
                        $logService->addLog('authorized by white list', $mailId);
                    }
                }
            }
            $messageStatus = $em->getRepository(MessageStatus::class)->find($status);
            $messageRecipient->setStatus($messageStatus);
            $em->persist($msgs);
            $em->flush();

            return true;
        }

        return $state;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/authorizedDomain', name: 'message_authorized_domain')]
    public function authorizedDomain($partitionTag, $mailId, $rid, Request $request)
    {
        //W = White and 2 is authorized
        if ($this->msgsToWblistDomain($partitionTag, $mailId, "W", $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderAuthorized'));
            $em = $this->em;
            $logService = new LogService($em);
            $logService->addLog('authorize for domain ', $mailId);
        }
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/bannedDomain', name: 'message_banned_domain')]
    public function bannedDomain($partitionTag, $mailId, $rid, Request $request)
    {
        //B = Black and msg status = 1 is banned
        if ($this->msgsToWblistDomain($partitionTag, $mailId, "B", $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderBanned'));
            $em = $this->em;
            $logService = new LogService($em);
            $logService->addLog('banned for domain ', $mailId);
        }
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /* Create rules of domain and change the message status and type (0 = user validate / 1 = captcha validate / 2 = group validate ) */
    public function msgsToWblistDomain($partitionTag, $mailId, $wb, $rid)
    {
        $em = $this->em;

        //select msgs and msgcpt
        $msgs = $em->getRepository(Msgs::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);
        if (!$msgs) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $msgrcpt = $em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);

        $emailSender = stream_get_contents($msgs->getSid()->getEmail(), -1, 0);

        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $msgs->getFromAddr(), $matches);
        if (isset($matches[0][0]) && filter_var($matches[0][0], FILTER_VALIDATE_EMAIL)) {
            $fromAddr = filter_var($matches[0][0], FILTER_VALIDATE_EMAIL);
            $emailSenderToWb = $emailSender != $fromAddr ? $fromAddr : $emailSender;
        }

        $emailRecipient = stream_get_contents($msgrcpt->getRid()->getEmail(), -1, 0);
        $domainEmailRecipient = strtolower(substr($emailRecipient, strpos($emailRecipient, '@') + 1));

        $userDomain = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domainEmailRecipient]);
        //todo check right of user connected admin of domain access
        $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $emailSenderToWb]));

        if (!$mailaddr) {
            $mailaddr = new Mailaddr();
            $mailaddr->setEmail($emailSenderToWb);
            $mailaddr->setPriority('6'); //priority for email by default
            $em->persist($mailaddr);
        }
        $wblist = $this->em->getRepository(Wblist::class)->findOneBy((['rid' => $userDomain, 'sid' => $mailaddr]));
        if (!$wblist) {
            $wblist = new Wblist($userDomain, $mailaddr);
        }
        $wblist->setWb($wb);
        $wblist->setPriority(Wblist::WBLIST_PRIORITY_USER);

        $em->persist($wblist);
        $em->flush();

        $listeMsgToRelease = $msgrcpt = $em->getRepository(Msgrcpt::class)->getAllMessageDomainRecipientsFromSender($emailSender, $msgrcpt->getRid()->getDomain());
        foreach ($listeMsgToRelease as $oneRcpt) {
            $oneMsgRcptObj = $em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $oneRcpt['partition_tag'], 'mailId' => $oneRcpt['mail_id'], 'rid' => $oneRcpt['rid']]);
            if ($wb == 'W') {
                $process = new Process([$this->getParameter('app.amavisd-release'), $oneRcpt['quar_loc'], $oneRcpt['secret_id'], $oneRcpt['recept_mail']]);
                $process->getCommandLine();
                $process->run(
                    function ($type, $buffer) use ($oneMsgRcptObj) {
                        $oneMsgRcptObj->setAmavisOutput($buffer);
                    }
                );
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
                $messageStatus = $em->getRepository(MessageStatus::class)->find(MessageStatus::AUTHORIZED);
            } else {
                $messageStatus = $em->getRepository(MessageStatus::class)->find(MessageStatus::BANNED);
            }

            $oneMsgRcptObj->setStatus($messageStatus);
            $em->persist($oneMsgRcptObj);
            $em->flush();

            $logService = new LogService($em);
            $logService->addLog('authorized by white list', $mailId);
        }

        $state = true;
        $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));
        return $state;
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/content', name: 'message_show_content')]
    public function showDetailMsgs($partitionTag, $mailId, $rid)
    {
        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);

        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $this->checkMailAccess($msgRcpt);

        return $this->render('message/content.html.twig', [
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid,
            'msgRcpt' => $msgRcpt
        ]);
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/iframe-content', name: 'message_show_iframe_content')]
    public function showIframeDetailMsgs($partitionTag, $mailId, $rid)
    {
        $mail_chunks = $this->em->getRepository(Quarantine::class)->findBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);
        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId, 'rid' => $rid]);

        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $this->checkMailAccess($msgRcpt);

        if (!$mail_chunks) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $rawMail = "";
        foreach ($mail_chunks as $mail_chunk) {
            $rawMail .= stream_get_contents($mail_chunk->getMailText(), -1, 0);
        }
        $rawMail = mb_convert_encoding($rawMail, 'UTF-8', 'auto');

        $message = Message::fromString($rawMail);
        $subject = $message->getSubject();

        //get attachments
        $attachments = $message->getAttachments()->filter(function ($attachment) {
            return $attachment->disposition !== 'inline';
        });

        $from = $message->getFrom();
        $textBody = $message->getTextBody();
        $htmlBody = $message->getHtmlBody();

        //Remove all script tags
        $htmlBody = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $htmlBody);
        //remove onclick=""
        $htmlBody = preg_replace('/\son\w+="[^"]*"/i', '', $htmlBody); // attributs entre guillemets doubles
        //remove onclick=''
        $htmlBody = preg_replace('/\son\w+=\'[^\']*\'/i', '', $htmlBody); // attributs entre guillemets simples

        //Remove original images
        $htmlBody = preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is',
            function ($matches) {
                return '[' . $this->translator->trans('Entities.Message.labels.imgDisabled') . ']';
            },
            $htmlBody
        );

        //Remove all links
        $htmlBody = preg_replace_callback(
            '/<a\b[^>]*\bhref=["\'][^"\']+["\'][^>]*>.*?<\/a>/is',  // Utilisation de "s" pour les retours à la ligne
            function ($matches) {
                return '[' . $this->translator->trans('Entities.Message.labels.linkDisabled') . ']';
            },
            $htmlBody
        );

        return $this->render('message/iframe_content.html.twig', [
            'textBody' => $textBody,
            'htmlBody' => $htmlBody,
            'from' => $from,
            'subject' => $subject,
            'attachments' => $attachments,
            'msgRcpt' => $msgRcpt
        ]);
    }

    private function checkMailAccess(Msgrcpt $msgRcpt)
    {
        $listAliases = $this->em->getRepository(User::class)->getListAliases($this->getUser());
        $accessibleRecipientEmails = array_map(function ($item) {
            return stream_get_contents($item->getEmail(), -1, 0);
        }, $listAliases);
        $accessibleRecipientEmails = array_merge([$this->getUser()->getEmailFromRessource()], $accessibleRecipientEmails);

        if (!$this->isGranted("ROLE_ADMIN") && !in_array($msgRcpt->getRid()->getEmailClear(), $accessibleRecipientEmails)) {
            throw new AccessDeniedException();
        }
    }
}
