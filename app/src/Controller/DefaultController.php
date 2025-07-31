<?php

namespace App\Controller;

use App\Amavis\ContentType;
use App\Entity\Domain;
use App\Amavis\MessageStatus;
use App\Entity\Mailaddr;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Alert;
use App\Entity\Wblist;
use App\Form\CaptchaFormType;
use App\Repository\UserRepository;
use App\Repository\MsgrcptRepository;
use App\Repository\WblistRepository;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MsgrcptSearchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        private Security $security,
        private MsgrcptSearchRepository $msgrcptSearchRepository,
    ) {
    }

    #[Route(path: '/', name: 'homepage')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $nbUntreadtedMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::UNTREATED
        );
        $nbAutorizeMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::AUTHORIZED
        );
        $nbBannedMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::BANNED
        );
        $nbDeletedMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::DELETED
        );
        $nbRestoredMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::RESTORED
        );
        $nbErrorMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::ERROR
        );
        $nbSpammedMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::SPAMMED
        );
        $nbVirusMsgByDay = $this->msgrcptSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::VIRUS
        );

        $latestMessageRecipients = $this->msgrcptSearchRepository->getSearchQuery($user)->setMaxResults(5)->getResult();
        $alerts = $this->em->getRepository(Alert::class)->findBy(['user' => $user->getId()], ['date' => 'DESC'], 5);
        $allAlerts = $this->em->getRepository(Alert::class)->findBy(['user' => $user->getId()], ['date' => 'DESC']);
        $unreadAlertsCount = count(array_filter($allAlerts, function ($alert) {
            return !$alert->getIsRead();
        }));

        $labels = array_map(function ($item) {
            return $item['timeIso'];
        }, $nbAutorizeMsgByDay);

        $msgs['untreated'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::UNTREATED);
        $msgs['authorized'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
        $msgs['banned'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::BANNED);
        $msgs['delete'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::DELETED);
        $msgs['restored'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::RESTORED);
        $msgs['error'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::ERROR);
        $msgs['spammed'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::SPAMMED);
        $msgs['virus'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::VIRUS);

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $domains = $this->em->getRepository(Domain::class)->findAll();
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $domains = $user->getDomains();
        } else {
            $domains = [];
        }

        foreach ($domains as $domain) {
            $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts($user, $domain);

            $data[$domain->getId()] = [
                'totalMsgCount' => array_reduce($users, function ($carry, $user) {
                    return $carry + $user['msgCount'];
                }, 0),
                'totalMsgBlockedCount' => array_reduce($users, function ($carry, $user) {
                    return $carry + $user['msgBlockedCount'];
                }, 0),
                'totalOutMsgCount' => array_reduce($users, function ($carry, $user) {
                    return $carry + $user['outMsgCount'];
                }, 0),
                'totalOutMsgBlockedCount' => array_reduce($users, function ($carry, $user) {
                    return $carry + $user['outMsgBlockedCount'];
                }, 0),
            ];
        }
        // Add data for "All" option
        $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts($user);
        $data['All'] = [
            'totalMsgCount' => array_reduce($users, function ($carry, $user) {
                return $carry + $user['msgCount'];
            }, 0),
            'totalMsgBlockedCount' => array_reduce($users, function ($carry, $user) {
                return $carry + $user['msgBlockedCount'];
            }, 0),
            'totalOutMsgCount' => array_reduce($users, function ($carry, $user) {
                return $carry + $user['outMsgCount'];
            }, 0),
            'totalOutMsgBlockedCount' => array_reduce($users, function ($carry, $user) {
                return $carry + $user['outMsgBlockedCount'];
            }, 0),
        ];

        $showAlertWidget = true;
        if ($user->getDomain() && $user->getDomain()->getSendUserAlerts() == false) {
            $showAlertWidget = false;
        }

        return $this->render('home/index.html.twig', [
                    'controller_name' => 'DefaultController',
                    'listDay' => $labels,
                    'nbUntreadtedMsgByDay' => $nbUntreadtedMsgByDay,
                    'nbAutorizeMsgByDay' => $nbAutorizeMsgByDay,
                    'nbBannedMsgByDay' => $nbBannedMsgByDay,
                    'nbDeletedMsgByDay' => $nbDeletedMsgByDay,
                    'nbRestoredMsgByDay' => $nbRestoredMsgByDay,
                    'nbErrorMsgByDay' => $nbErrorMsgByDay,
                    'nbSpammedMsgByDay' => $nbSpammedMsgByDay,
                    'nbVirusMsgByDay' => $nbVirusMsgByDay,
                    'latestMessageRecipients' => $latestMessageRecipients,
                    'users' => $users,
                    'alerts' => $alerts,
                    'unreadAlertsCount' => $unreadAlertsCount,
                    'msgs' => $msgs,
                    'domains' => $domains,
                    'data' => $data,
                    'showAlert' => $showAlertWidget
        ]);
    }

    #[Route(path: '{userEmail}/messages_stats/', name: 'messages_stats', methods: 'GET')]
    public function showMessagesStats(
        string $userEmail,
        MsgrcptRepository $msgrcptRepository,
        UserRepository $userRepository
    ): Response {
        $connection = $this->em->getConnection();

        $user = $userRepository->findOneBy(['email' => $userEmail]);

        $query = $msgrcptRepository->findByEmailRecipient($userEmail);
        $messages = $query->getArrayResult();

        $sqlOutMessages = '
            SELECT out_msgs.*, mr.status_id as status, mr.bspam_level as bspamLevel, mr.content
            FROM out_msgs
            JOIN out_msgrcpt AS mr ON out_msgs.mail_id = mr.mail_id
            WHERE out_msgs.from_addr = :userEmail
        ';
        $stmtOutMessages = $connection->executeQuery($sqlOutMessages, ['userEmail' => $userEmail]);
        $outMessages = $stmtOutMessages->fetchAllAssociative();

        // Determine status using provided logic
        $statusCounts = [];

        foreach ($messages as $message) {
            $status = $this->determineStatus($message, $user->getDomain()->getLevel());
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = ['name' => $status, 'qty' => 0, 'qty_out' => 0];
            }
            $statusCounts[$status]['qty']++;
        }

        foreach ($outMessages as $outMessage) {
            $status = $this->determineStatus($outMessage, $user->getDomain()->getLevel());
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = ['name' => $status, 'qty' => 0, 'qty_out' => 0];
            }
            if ($status != 'untreated') {
                $statusCounts[$status]['qty_out']++;
            }
        }

        // Convert to array and return to view
        $messagesStatus = array_values($statusCounts);

        // Get the count of sql_limit_report entries where id = $userEmail
        $sqlLimitReports = '
            SELECT COUNT(*) AS count
            FROM sql_limit_report
            WHERE mail_id = :userEmail
        ';
        $stmtLimitReports = $connection->executeQuery($sqlLimitReports, ['userEmail' => $userEmail]);
        $limitReports = $stmtLimitReports->fetchAssociative();

        // Add Sql_Limit_Report to $messagesStatus
        $messagesStatus[] = [
            'name' => 'quota',
            'qty' => 0,
            'qty_out' => $limitReports ? $limitReports['count'] : 0
        ];

        return $this->render('home/messages_stats.html.twig', [
            'msgs_status' => $messagesStatus,
        ]);
    }

    /**
     * Determine the status of a message based on its properties
     * @param array<string, mixed> $message
     * @return string
     */
    private function determineStatus(array $message, float $level): string
    {
        if ($message['status'] !== null) {
            return MessageStatus::getStatusName($message['status']);
        } elseif (
            $message['status'] === null && $message['bspamLevel'] > $level
            && $message['content'] !== ContentType::CLEAN && $message['content'] !== ContentType::VIRUS
        ) {
            return 'spam';
        } elseif ($message['content'] === ContentType::VIRUS) {
            return 'virus';
        } else {
            return 'untreated';
        }
    }

    #[Route(path: '/check/{token}', name: 'check_message', methods: 'GET|POST')]
    public function checkCaptcha(
        string $token,
        Request $request,
        WblistRepository $wblistRepository,
        Service\MessageService $messageService,
        Service\CryptEncryptService $cryptEncrypt,
    ): Response {
        $em = $this->em;
        $confirm = "";

        list($message, $domain, $messageRecipients) = $this->decryptMessageToken($cryptEncrypt, $token);

        if (!$message) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        if (!$domain) {
            throw $this->createNotFoundException('The domain does not exist.');
        }

        $sender = $message->getSid();
        $senderEmail = $sender->getEmailClear();

        // Keep only recipients that are NOT already in a wblist. This avoids,
        // for a instance, a blocked sender to authorise himself.
        $messageRecipients = array_filter(
            $messageRecipients,
            function ($recipient) use ($wblistRepository, $sender) {
                return !$wblistRepository->isRecipientInSenderList($sender, $recipient->getRid());
            }
        );

        if (!empty($domain->getMessage())) {
            $content = $domain->getMessage();
        } else {
            $content = $this->translator->trans('Message.Captcha.defaultCaptchaPageContent');
        }

        $form = $this->createForm(CaptchaFormType::class, null);
        $form->handleRequest($request); //todo gérer l'erreur si la page est rechargée

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($domain->getConfirmCaptchaMessage())) {
                $confirm = $domain->getConfirmCaptchaMessage();
            } else {
                $confirm = $this->translator->trans('Message.Captcha.defaultCaptchaConfirmMsg');
            }

            $recipient = $messageRecipients[0] ?? null;
            if ($recipient) {
                $emailDest = $recipient->getRid()->getEmailClear();
                $emailDest = $emailDest ? $emailDest : '';
                $confirm = str_replace('[EMAIL_DEST]', $emailDest, $confirm);
            }

            // Test if the sender is the same than the posted email field and if the mail has not been yet treated
            if (!$message->getStatus() && $form->has('email') && $form->get('email')->getData() == $senderEmail) {
                if ($form->has('emailEmpty') && empty($form->get('emailEmpty')->getData())) { // Test HoneyPot
                    $messageService->authorize($message, $messageRecipients, Wblist::WBLIST_TYPE_AUTHENTICATION);
                    $message->setValidateCaptcha(time());
                    $em->persist($message);
                    $em->flush();
                } else {
                    $this->addFlash('error', $this->translator->trans('Message.Flash.checkMailUnsuccessful'));
                }
            } else {
                $this->addFlash('error', $this->translator->trans('Message.Flash.checkMailUnsuccessful'));
            }
        } else {
            $form->get('email')->setData($senderEmail);
        }

        return $this->render('message/captcha.html.twig', [
            'token' => $token,
            'mailToValidate' => $senderEmail,
            'form' => $form->createView(),
            'confirm' => $confirm,
            'content' => $content
        ]);
    }

    /**
     * Change the locale for the current user.
     */
    #[Route(path: '/setlocale/{language}', name: 'setlocale')]
    public function setLocaleAction(Request $request, EntityManagerInterface $em, ?string $language = null): Response
    {
        if (null != $language) {
            $request->getSession()->set('_locale', $language);
        }

        $url = $request->headers->get('referer');
        if (empty($url)) {
            $url = $this->container->get('router')->generate('message');
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $user->setPreferedLang($language);
            $em->persist($user);
            $em->flush();
        }

        $user->setPreferedLang($language);
        $em->persist($user);
        $em->flush();

        return new RedirectResponse($url);
    }

    /**
     * @return array{?Msgs, ?Domain, Msgrcpt[]}
     */
    private function decryptMessageToken(Service\CryptEncryptService $cryptEncryptService, string $token): array
    {
        list($expiry, $decryptedToken) = $cryptEncryptService->decrypt($token);

        if ($expiry < time()) {
            throw $this->createNotFoundException('The token has expired.');
        }

        if ($decryptedToken === false) {
            throw $this->createNotFoundException('The token is invalid.');
        }

        $tokenParts = explode('%%%', $decryptedToken);

        if (count($tokenParts) < 4) {
            throw $this->createNotFoundException('The token is invalid.');
        }

        $mailId = $tokenParts[0];
        $partitionTag = (int) $tokenParts[2];
        $domainId = $tokenParts[3];

        $message = $this->em->getRepository(Msgs::class)->findOneByMailId($partitionTag, $mailId);
        $domain = $this->em->getRepository(Domain::class)->find($domainId);

        $messageRecipients = [];
        if ($message) {
            $messageRecipients = $this->em->getRepository(Msgrcpt::class)->findByMessage($message);

            $messageRecipients = array_filter($messageRecipients, function (Msgrcpt $msgrcpt) use ($domain) {
                return $msgrcpt->getRid()->getReverseDomain() == $domain->getDomain();
            });
        }

        return [$message, $domain, $messageRecipients];
    }
}
