<?php

namespace App\Controller;

use App\Controller\MessageController;
use App\Model\ValidationSource;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Alert;
use App\Form\CaptchaFormType;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController {

    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        private Security $security) {
    }

    #[Route(path: '/', name: 'homepage')]
    public function index(): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $alias = $this->em->getRepository(User::class)->findBy(['originalUser' => $user->getId()]);
        $nbUntreadtedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::UNTREATED, $alias);
        $nbAutorizeMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::AUTHORIZED, $alias);
        $nbBannedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::BANNED, $alias);
        $nbDeletedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::DELETED, $alias);
        $nbRestoredMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::RESTORED, $alias);
        $nbErrorMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::ERROR, $alias);
        $nbSpammedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::SPAMMED, $alias);
        $nbVirusMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($user, MessageStatus::VIRUS, $alias);
        $latest_msgs = $this->em->getRepository(Msgs::class)->search($user, null, null, null, null, null, 5);
        $alerts = $this->em->getRepository(Alert::class)->findBy(['user' => $user->getId()], ['date' => 'DESC'], 5);
        $all_alerts = $this->em->getRepository(Alert::class)->findBy(['user' => $user->getId()], ['date' => 'DESC']);
        $unreadAlertsCount = count(array_filter($all_alerts, function($all_alert) {
            return !$all_alert->getIsRead();
        }));

        $labels = array_map(function ($item) {
            return $item['time_iso'];
        }, $nbAutorizeMsgByDay);

        $msgs['untreated'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::UNTREATED, $alias);
        $msgs['authorized'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::AUTHORIZED, $alias);
        $msgs['banned'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::BANNED, $alias);
        $msgs['delete'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::DELETED, $alias);
        $msgs['restored'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::RESTORED, $alias);
        $msgs['error'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::ERROR, $alias);
        $msgs['spammed'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::SPAMMED, $alias);
        $msgs['virus'] = $this->em->getRepository(Msgs::class)->countByType($user, MessageStatus::VIRUS, $alias);

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $domains = $this->em->getRepository(Domain::class)->findAll();
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $domains = $user->getDomains();
        } else {
            $domains = [];
        }

        foreach ($domains as $domain) {
            $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts($user, $domain->getId());

            $data[$domain->getId()] = [
                'totalMsgCount' => array_reduce($users, function($carry, $user) {
                    return $carry + $user['msgCount'];
                }, 0),
                'totalMsgBlockedCount' => array_reduce($users, function($carry, $user) {
                    return $carry + $user['msgBlockedCount'];
                }, 0),
                'totalOutMsgCount' => array_reduce($users, function($carry, $user) {
                    return $carry + $user['outMsgCount'];
                }, 0),
                'totalOutMsgBlockedCount' => array_reduce($users, function($carry, $user) {
                    return $carry + $user['outMsgBlockedCount'];
                }, 0),
            ];
        }
        // Add data for "All" option
        $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts($user);
        $data['All'] = [
            'totalMsgCount' => array_reduce($users, function($carry, $user) {
                return $carry + $user['msgCount'];
            }, 0),
            'totalMsgBlockedCount' => array_reduce($users, function($carry, $user) {
                return $carry + $user['msgBlockedCount'];
            }, 0),
            'totalOutMsgCount' => array_reduce($users, function($carry, $user) {
                return $carry + $user['outMsgCount'];
            }, 0),
            'totalOutMsgBlockedCount' => array_reduce($users, function($carry, $user) {
                return $carry + $user['outMsgBlockedCount'];
            }, 0),
        ];

        $showAlertWidget = true;
        if ($user->getDomain() && $user->getDomain()->getSendUserAlerts() == false)
        {
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
                    'latest_msgs' => $latest_msgs,
                    'users' => $users,
                    'alerts' => $alerts,
                    'unreadAlertsCount' => $unreadAlertsCount,
                    'msgs' => $msgs,
                    'domains' => $domains,
                    'data' => $data,
                    'showAlert' => $showAlertWidget
        ]);
    }

    #[Route(path: '{user_email}/messages_stats/', name: 'messages_stats', methods: 'GET')]
    public function showMessagesStats(string $user_email): Response {
        $connection = $this->em->getConnection();

        // Fetch domain_id from user table
        $sqlUser = 'SELECT domain_id FROM users WHERE email = :user_email';
        $stmtUser = $connection->executeQuery($sqlUser, ['user_email' => $user_email]);
        $user = $stmtUser->fetchAssociative();
        $domain_id = $user['domain_id'];

        // Fetch messages with status
        $sqlMessages = '
            SELECT msgs.*, ms.name AS status_name, mr.status_id, mr.bspam_level, mr.content, d.level
            FROM msgs
            JOIN msgrcpt AS mr ON msgs.mail_id = mr.mail_id
            JOIN maddr ON mr.rid = maddr.id
            LEFT JOIN message_status AS ms ON msgs.status_id = ms.id
            JOIN domain AS d ON d.id = :domain_id
            WHERE maddr.email = :user_email
            AND msgs.quar_type != ""
        ';
        $stmtMessages = $connection->executeQuery($sqlMessages, ['user_email' => $user_email, 'domain_id' => $domain_id]);
        $messages = $stmtMessages->fetchAllAssociative();

        // Fetch out messages with status
        $sqlOutMessages = '
            SELECT out_msgs.*, ms.name AS status_name, mr.status_id, mr.bspam_level, mr.content, d.level
            FROM out_msgs
            JOIN out_msgrcpt AS mr ON out_msgs.mail_id = mr.mail_id
            LEFT JOIN message_status AS ms ON out_msgs.status_id = ms.id
            JOIN domain AS d ON d.id = :domain_id
            WHERE out_msgs.from_addr = :user_email
        ';
        $stmtOutMessages = $connection->executeQuery($sqlOutMessages, ['user_email' => $user_email, 'domain_id' => $domain_id]);
        $outMessages = $stmtOutMessages->fetchAllAssociative();

        // Determine status using provided logic
        $statusCounts = [];

        foreach ($messages as $message) {
            $status = $this->determineStatus($message);
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = ['name' => $status, 'qty' => 0, 'qty_out' => 0];
            }
            $statusCounts[$status]['qty']++;
        }

        foreach ($outMessages as $outMessage) {
            $status = $this->determineStatus($outMessage);
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = ['name' => $status, 'qty' => 0, 'qty_out' => 0];
            }
            if ($status != 'untreated') {
                $statusCounts[$status]['qty_out']++;
            }
        }

        // Convert to array and return to view
        $msgs_status = array_values($statusCounts);

        // Get the count of sql_limit_report entries where id = $user_email
        $sqlLimitReports = '
            SELECT COUNT(*) AS count
            FROM sql_limit_report
            WHERE mail_id = :user_email
        ';
        $stmtLimitReports = $connection->executeQuery($sqlLimitReports, ['user_email' => $user_email]);
        $limitReports = $stmtLimitReports->fetchAssociative();

        // Add Sql_Limit_Report to $msgs_status
        $msgs_status[] = [
            'name' => 'quota',
            'qty' => 0,
            'qty_out' => $limitReports['count']
        ];

        return $this->render('home/messages_stats.html.twig', [
            'msgs_status' => $msgs_status
        ]);
    }

    /**
     * Determine the status of a message based on its properties
     * @param array<string, mixed> $message
     * @return string
     */
    private function determineStatus(array $message): string {
        if ($message['status_name'] !== null) {
            return $message['status_name'];
        } elseif ($message['status_id'] === null && $message['bspam_level'] > $message['level'] && $message['content'] !== 'C' && $message['content'] !== 'V') {
            return 'spam';
        } elseif ($message['content'] === 'V') {
            return 'virus';
        } else {
            return 'untreated';
        }
    }

    #[Route(path: '/check/{token}', name: 'check_message', methods: 'GET|POST')]
    public function checkCaptcha(string $token, Request $request, Service\MessageService $messageService, Service\CryptEncryptService $cryptEncrypt): Response {
        $em = $this->em;
        $confirm = "";

        list($message, $domain, $messageRecipients) = $this->decryptMessageToken($cryptEncrypt, $token);

        if (!$message) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        if (!$domain) {
            throw $this->createNotFoundException('The domain does not exist.');
        }

        $senderEmail = stream_get_contents($message->getSid()->getEmail(), -1, 0);
        $content = !empty($domain->getMessage()) ? $domain->getMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaPageContent');

        $form = $this->createForm(CaptchaFormType::class, null);
        $form->handleRequest($request); //todo gérer l'erreur si la page est rechargée

        if ($form->isSubmitted() && $form->isValid()) {
            $confirm = !empty($domain->getConfirmCaptchaMessage()) ? $domain->getConfirmCaptchaMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaConfirmMsg');
            $recipient = $messageRecipients[0] ?? null;
            if ($recipient) {
                $confirm = str_replace('[EMAIL_DEST]', stream_get_contents($recipient->getRid()->getEmail(), -1, 0), $confirm);
            }
            if (!$message->getStatus() && $form->has('email') && $form->get('email')->getData() == $senderEmail) { // Test is the sender is the same than the posted email field and if the mail has not been yet treated
                if ($form->has('emailEmpty') && empty($form->get('emailEmpty')->getData())) { // Test HoneyPot
                    $messageService->authorize($message, $messageRecipients, ValidationSource::captcha);
                    $message->setValidateCaptcha(time());
                    $em->persist($message);
                    $em->flush();
                    $mailId = stream_get_contents($message->getMailId(), -1, 0);
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
     *
     *
     * @param string  $language
     * @return Response
     */
    #[Route(path: '/setlocale/{language}', name: 'setlocale')]
    public function setLocaleAction(Request $request, EntityManagerInterface $em, $language = null) {
        if (null != $language) {
            $request->getSession()->set('_locale', $language);
        }

        $url = $request->headers->get('referer');
        if (empty($url)) {
            $url = $this->container->get('router')->generate('message');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->security->isGranted('ROLE_PREVIOUS_ADMIN')){
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
            $messageRecipients = array_filter($messageRecipients, function(Msgrcpt $msgrcpt) use ($domain) {
                return $msgrcpt->getRid()->getReverseDomain() == $domain->getDomain();
            });
        }

        return [$message, $domain, $messageRecipients];
    }
}
