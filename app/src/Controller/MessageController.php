<?php

namespace App\Controller;

use App\Entity\Mailaddr;
use App\Amavis\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\Quarantine;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\ActionsFilterType;
use App\Repository\MsgrcptSearchRepository;
use App\Repository\MsgsRepository;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em
    ) {
    }

    #[Route(path: '/{type}', name: 'message')]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        MsgrcptSearchRepository $msgrcptSearchRepository,
        MsgsRepository $msgsRepository,
        ?string $type = null
    ): Response {
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
                    $messageStatus = MessageStatus::SPAMMED;
                    break;
                case 'virus':
                    $subTitle = 'Entities.Message.virus';
                    $messageStatus = MessageStatus::VIRUS;
                    $messageActions = [];
                    break;
                case 'banned':
                    $subTitle = 'Entities.Message.banned';
                    $messageStatus = MessageStatus::BANNED;
                    $messageActions = [
                        'Message.Actions.Autorized' => 'authorized',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    break;
                case 'authorized':
                    $subTitle = 'Entities.Message.authorized';
                    $messageActions = [
                        'Message.Actions.Banned' => 'banned',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    $messageStatus = MessageStatus::AUTHORIZED;
                    break;
                case 'delete':
                    $subTitle = 'Entities.Message.delete';
                    $messageStatus = MessageStatus::DELETED;
                    break;
                case 'restored':
                    $subTitle = 'Entities.Message.restored';
                    $messageStatus = MessageStatus::RESTORED;
                    break;

                default:
                    $subTitle = 'Entities.Message.untreated';
                    $messageActions = [
                        'Message.Actions.Restore' => 'restore',
                        'Message.Actions.Autorized' => 'authorized',
                        'Message.Actions.Banned' => 'banned',
                        'Message.Actions.Delete' => 'delete',
                    ];
                    $messageStatus = MessageStatus::UNTREATED;
                    break;
            }
        } else {
            $messageActions = [
                'Message.Actions.Restore' => 'restore',
                'Message.Actions.Autorized' => 'authorized',
                'Message.Actions.Banned' => 'banned',
                'Message.Actions.Delete' => 'delete',
            ];
            $subTitle = 'Entities.Message.untreated';
            $messageStatus = null;
        }

        $filterForm = $this->createForm(ActionsFilterType::class, options: [
            'avalaibleActions' => $messageActions,
            'action' => $this->generateUrl('message_batch')
        ]);

        if ($messageStatus === MessageStatus::DELETED || $messageStatus === MessageStatus::VIRUS) {
            $filterForm->remove('actions');
        }

        /** @var User $user */
        $user = $this->getUser();

        $sortParams = null;
        if ($request->query->has('sort') && $request->query->has('direction')) {
            $sortParams = [
                'sort' => $request->query->getString('sort'),
                'direction' => $request->query->getString('direction'),
            ];
        }

        $searchKey = trim($request->query->getString('search'));
        $messagesRecipientsQuery = $msgrcptSearchRepository->getSearchQuery(
            $user,
            $messageStatus,
            $searchKey,
            $sortParams,
        );

        $perPage = (int) $this->getParameter('app.per_page_global');
        if ($request->query->has('per_page') && $request->query->getInt('per_page') > 0) {
            $perPage = $request->query->getInt('per_page', $perPage);
            $request->getSession()->set('per_page', $perPage);
        } elseif ($request->getSession()->has('per_page') && $request->getSession()->get('per_page') > 0) {
            $perPage = $request->getSession()->get('per_page');
        }
        $filterForm->get('per_page')->setData($perPage);

        $messagesRecipients = $paginator->paginate(
            $messagesRecipientsQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
            ]
        );
        return $this->render('message/index.html.twig', [
            'subTitle' => $subTitle,
            'messagesRecipients' => $messagesRecipients,
            'messageStatus' => $messageStatus,
            'filter_form' => $filterForm->createView()
        ]);
    }


    #[Route(path: '/{partitionTag}/{mailId}/{rid}/show/', name: 'message_show', methods: 'GET')]
    public function showAction(int $partitionTag, string $mailId, int $rid): Response
    {

        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid
        ]);
        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }
        $this->checkMailAccess($msgRcpt);

        $msg = $this->em->getRepository(Msgs::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
        ]);

        $wbListInfos = $this->em->getRepository(Wblist::class)->getWbListInfoForSender(
            $msg->getSid()->getEmailClear(),
            $msgRcpt->getRid()->getEmailClear(),
        );
        return $this->render('message/show.html.twig', [
            'controller_name' => 'MessageController',
            'msg' => $msg,
            'msgRcpt' => $msgRcpt,
            'wblistInfo' => $wbListInfos
        ]);
    }

    /**
     * Delete a message entity.
     */
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/delete/', name: 'message_delete', methods: 'GET')]
    public function deleteAction(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
        Service\MessageService $messageService,
    ): Response {
        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->em->getRepository(Msgrcpt::class)->findOneByMessageAndRid($message, $rid);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($messageRecipient);

        if ($messageService->delete($message, $messageRecipient)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.messageDeleted'));
            $logService->addLog('delete', $mailId);
        }

        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    #[Route(path: '/batch/{action}', name: 'message_batch', methods: 'POST', options: ['expose' => true])]
    public function batchMessageAction(
        Request $request,
        Service\LogService $logService,
        Service\MessageService $messageService,
        ?string $action = null,
    ): Response {
        if ($action) {
            foreach ($request->request->all('id') as $obj) {
                list($message, $messageRecipient) = $this->fetchMessageFromBatchId($obj);
                $mailId = $message->getMailIdAsString();

                $this->checkMailAccess($messageRecipient);

                switch ($action) {
                    case 'authorized':
                        $messageService->authorize($message, [$messageRecipient], Wblist::WBLIST_TYPE_USER);
                        $logService->addLog('authorized batch', $mailId);
                        break;
                    case 'banned':
                        $messageService->ban($message, [$messageRecipient], Wblist::WBLIST_TYPE_USER);
                        $logService->addLog('banned batch', $mailId);
                        break;
                    case 'delete':
                        $messageService->delete($message, $messageRecipient);
                        $logService->addLog('delete batch', $mailId);
                        break;
                    case 'restore':
                        $messageService->restore($message, $messageRecipient);
                        $logService->addLog('restore batch', $mailId);
                        break;
                }
            }
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/authorized', name: 'message_authorized')]
    public function authorized(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
        Service\MessageService $messageService,
    ): Response {
        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->em->getRepository(Msgrcpt::class)->findOneByMessageAndRid($message, $rid);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($messageRecipient);

        if ($messageService->authorize($message, [$messageRecipient], Wblist::WBLIST_TYPE_USER)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderAuthorized'));
            $logService->addLog('authorized', $mailId);
        }

        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/banned', name: 'message_banned')]
    public function banned(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
        Service\MessageService $messageService,
    ): Response {
        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->em->getRepository(Msgrcpt::class)->findOneByMessageAndRid($message, $rid);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($messageRecipient);

        if ($messageService->ban($message, [$messageRecipient], Wblist::WBLIST_TYPE_USER)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderBanned'));
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
     */
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/restore', name: 'message_restore')]
    public function restore(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
        Service\MessageService $messageService,
    ): Response {
        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->em->getRepository(Msgrcpt::class)->findOneByMessageAndRid($message, $rid);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        $this->checkMailAccess($messageRecipient);

        if ($messageService->restore($message, $messageRecipient)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.messageRestored'));
            $logService->addLog('restore', $mailId);
        }

        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/authorizedDomain', name: 'message_authorized_domain')]
    public function authorizedDomain(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
    ): RedirectResponse {
        if ($this->msgsToWblistDomain($partitionTag, $mailId, "W", $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderAuthorized'));
            $logService->addLog('authorize for domain ', $mailId);
        }

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/{partitionTag}/{mailId}/{rid}/bannedDomain', name: 'message_banned_domain')]
    public function bannedDomain(
        int $partitionTag,
        string $mailId,
        int $rid,
        Request $request,
        Service\LogService $logService,
    ): RedirectResponse {
        if ($this->msgsToWblistDomain($partitionTag, $mailId, "B", $rid)) {
            $this->addFlash('success', $this->translator->trans('Message.Flash.senderBanned'));
            $logService->addLog('banned for domain ', $mailId);
        }

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    /**
     * Create rules of domain and change the message status and type.
     */
    public function msgsToWblistDomain(int $partitionTag, string $mailId, string $wb, int $rid): bool
    {
        $em = $this->em;

        //select msgs and msgcpt
        $msgs = $em->getRepository(Msgs::class)->findOneBy(['partitionTag' => $partitionTag, 'mailId' => $mailId]);
        if (!$msgs) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $msgrcpt = $em->getRepository(Msgrcpt::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid,
        ]);

        $emailSender = $msgs->getSid()->getEmailClear();

        if ($emailSender === null) {
            throw $this->createNotFoundException('Unable to retrieve the email sender');
        }

        $fromAddr = $msgs->getFromMimeAddress()?->getAddress();

        $emailSenderToWb = ($fromAddr && $emailSender != $fromAddr) ? $fromAddr : $emailSender;

        $emailRecipient = $msgrcpt->getRid()->getEmailClear();

        if ($emailRecipient === null) {
            throw $this->createNotFoundException('Unable to retrieve the email recipient');
        }

        $domainEmailRecipient = strtolower(substr($emailRecipient, strpos($emailRecipient, '@') + 1));

        $userDomain = $this->em->getRepository(User::class)->findOneBy(['email' => '@' . $domainEmailRecipient]);
        //todo check right of user connected admin of domain access
        $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $emailSenderToWb]));

        if (!$mailaddr) {
            $mailaddr = new Mailaddr();
            $mailaddr->setEmail($emailSenderToWb);
            $mailaddr->setPriority(6); //priority for email by default
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

        $listeMsgToRelease = $msgrcpt = $em->getRepository(Msgrcpt::class)->getAllMessageDomainRecipientsFromSender(
            $emailSender,
            $msgrcpt->getRid()->getDomain()
        );
        foreach ($listeMsgToRelease as $oneRcpt) {
            $oneMsgRcptObj = $em->getRepository(Msgrcpt::class)->findOneBy([
                'partitionTag' => $oneRcpt['partition_tag'],
                'mailId' => $oneRcpt['mail_id'],
                'rid' => $oneRcpt['rid'],
            ]);
            if ($wb == 'W') {
                $process = new Process([
                    $this->getParameter('app.amavisd-release'),
                    $oneRcpt['quar_loc'],
                    $oneRcpt['secret_id'],
                    $oneRcpt['recept_mail'],
                ]);
                $process->getCommandLine();
                $process->run(
                    function ($type, $buffer) use ($oneMsgRcptObj) {
                        $oneMsgRcptObj->setAmavisOutput($buffer);
                    }
                );
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
                $messageStatus = MessageStatus::AUTHORIZED;
            } else {
                $messageStatus = MessageStatus::BANNED;
            }

            $oneMsgRcptObj->setStatus($messageStatus);
            $em->persist($oneMsgRcptObj);
            $em->flush();

            $logService = new Service\LogService($em);
            $logService->addLog('authorized by white list', $mailId);
        }

        $state = true;
        $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));
        return $state;
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/content', name: 'message_show_content')]
    public function showDetailMsgs(int $partitionTag, string $mailId, int $rid): Response
    {
        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid,
        ]);

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
    public function showIframeDetailMsgs(int $partitionTag, string $mailId, int $rid): Response
    {
        $mailChunks = $this->em->getRepository(Quarantine::class)->findBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
        ]);
        $msgRcpt = $this->em->getRepository(Msgrcpt::class)->findOneBy([
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid,
        ]);

        if (!$msgRcpt) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $this->checkMailAccess($msgRcpt);

        if (!$mailChunks) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        $rawMail = "";
        foreach ($mailChunks as $mailChunk) {
            $rawMail .= stream_get_contents($mailChunk->getMailText(), -1, 0);
        }
        $rawMail = mb_convert_encoding($rawMail, 'UTF-8', 'auto');

        if (!$rawMail) {
            throw $this->createNotFoundException('The message does not exist.');
        }

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

    private function checkMailAccess(Msgrcpt $msgRcpt): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $listAliases = $this->em->getRepository(User::class)->getListAliases($user);
        $accessibleRecipientEmails = array_map(function ($item) {
            return stream_get_contents($item->getEmail(), -1, 0);
        }, $listAliases);
        $accessibleRecipientEmails = array_merge([$user->getEmailFromRessource()], $accessibleRecipientEmails);

        if (
            !$this->isGranted("ROLE_ADMIN") &&
            !in_array($msgRcpt->getRid()->getEmailClear(), $accessibleRecipientEmails)
        ) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return array{Msgs, Msgrcpt}
     */
    private function fetchMessageFromBatchId(string $id): array
    {
        $decodedId = json_decode($id);

        if (!is_array($decodedId) || count($decodedId) !== 3) {
            throw $this->createNotFoundException('Invalid batch id');
        }

        list($partitionTag, $mailId, $rid) = $decodedId;

        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            throw $this->createNotFoundException('Message does not exist');
        }

        $messageRecipient = $this->em->getRepository(Msgrcpt::class)->findOneByMessageAndRid($message, $rid);

        if (!$messageRecipient) {
            throw $this->createNotFoundException('Message recipient does not exist');
        }

        return [$message, $messageRecipient];
    }
}
