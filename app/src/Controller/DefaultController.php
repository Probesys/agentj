<?php

namespace App\Controller;

use App\Controller\MessageController;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Entity\Alert;
use App\Form\CaptchaFormType;
use App\Service\CryptEncryptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController {

    private $translator;
    private $em;
    private $security;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, Security $security) {
        $this->translator = $translator;
        $this->em = $em;
        $this->security = $security;
    }

    #[Route(path: '/', name: 'homepage')]
    public function index() {
        $alias = $this->em->getRepository(User::class)->findBy(['originalUser' => $this->getUser()->getId()]);
        $nbUntreadtedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::UNTREATED, $alias);
        $nbAutorizeMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::AUTHORIZED, $alias);
        $nbBannedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::BANNED, $alias);
        $nbDeletedMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::DELETED, $alias);
        $nbRestoredMsgByDay = $this->em->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::RESTORED, $alias);
        $latest_msgs = $this->em->getRepository(Msgs::class)->search($this->getUser(), 'All', null, null, null, null, 5);
        $alerts = $this->em->getRepository(Alert::class)->findBy(['user' => $this->getUser()->getId()], ['date' => 'DESC'], 5);
        $unreadAlertsCount = count(array_filter($alerts, function($alert) {
            return !$alert->getIsRead();
        }));

        $labels = array_map(function ($item) {
            return $item['time_iso'];
        }, $nbUntreadtedMsgByDay);

        $msgs['untreated'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::UNTREATED, $alias);
        $msgs['authorized'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::AUTHORIZED, $alias);
        $msgs['banned'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::BANNED, $alias);
        $msgs['delete'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::DELETED, $alias);
        $msgs['restored'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::RESTORED, $alias);
        $msgs['error'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::ERROR, $alias);
        $msgs['spammed'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::SPAMMED, $alias);
        $msgs['virus'] = $this->em->getRepository(Msgs::class)->countByType($this->getUser(), MessageStatus::VIRUS, $alias);

        $domains = $this->em->getRepository(Domain::class)->findAll();
        foreach ($domains as $domain) {
            $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts($domain->getId());

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
        $users = $this->em->getRepository(User::class)->getUsersWithRoleAndMessageCounts();
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

        return $this->render('home/index.html.twig', [
                    'controller_name' => 'DefaultController',
                    'listDay' => $labels,
                    'nbUntreadtedMsgByDay' => $nbUntreadtedMsgByDay,
                    'nbAutorizeMsgByDay' => $nbAutorizeMsgByDay,
                    'nbBannedMsgByDay' => $nbBannedMsgByDay,
                    'nbDeletedMsgByDay' => $nbDeletedMsgByDay,
                    'nbRestoredMsgByDay' => $nbRestoredMsgByDay,
                    'latest_msgs' => $latest_msgs,
                    'users' => $users,
                    'alerts' => $alerts,
                    'unreadAlertsCount' => $unreadAlertsCount,
                    'msgs' => $msgs,
                    'domains' => $domains,
                    'data' => $data
        ]);
    }

    #[Route(path: '{user_email}/messages_stats/', name: 'messages_stats', methods: 'GET')]
    public function showMessagesStats($user_email, Request $request) {
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

        return $this->render('home/messages_stats.html.twig', [
            'msgs_status' => $msgs_status
        ]);
    }

    private function determineStatus($message) {
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
    public function checkCaptcha($token, Request $request, MessageController $messageController, CryptEncryptService $cryptEncrypt) {
        $em = $this->em;
        $confirm = "";

        $decrypt = $cryptEncrypt->decrypt($token);
        $mailId = null;
        $date = null;
        $rid = null;
        if (isset($decrypt[0]) && isset($decrypt[1])) {
            $date = $decrypt[0];
            $msg = explode('%%%', $decrypt[1]);
            if (isset($msg[0]) && isset($msg[1])) {
                $mailId = $msg[0];
                if (isset($msg[0]) && isset($msg[4])) {
                    $rid = $msg[4];
                }
                $partitionTag = $msg[2];
            } else {
                throw $this->createNotFoundException('This page does not exist');
            }
        }

        if (!$mailId) {
            throw $this->createNotFoundException('This page does not exist');
        }

        /* @var $msgs Msgs */
        $msgs = $this->em->getRepository(Msgs::class)->findOneBy(['mailId' => $mailId]);

        if (!$msgs) {
            throw $this->createNotFoundException('This page does not exist');
        }

        $content = "";
        $domainId = $msg[3]; //need domain Id to get message
        $domain = $em->getRepository(Domain::class)->find($domainId);
        if (!$domain) {
            throw $this->createNotFoundException('This page does not exist');
        }

        $senderEmail = stream_get_contents($msgs->getSid()->getEmail(), -1, 0);
        if ($domain) {
            $content = !empty($domain->getMessage()) ? $domain->getMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaPageContent');
        }

        $form = $this->createForm(CaptchaFormType::class, null);
        $form->handleRequest($request); //todo gérer l'erreur si la page est rechargée
        if ($form->isSubmitted() && $form->isValid()) {
            $confirm = !empty($domain->getConfirmCaptchaMessage()) ? $domain->getConfirmCaptchaMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaConfirmMsg');
            $recipient = $em->getRepository(Msgrcpt::class)->findOneBy(['mailId' => $mailId]);
            if ($recipient) {
                $confirm = str_replace('[EMAIL_DEST]', stream_get_contents($recipient->getRid()->getEmail(), -1, 0), $confirm);
            }
                if (!$msgs->getStatus() && $form->has('email') && $form->get('email')->getData() == $senderEmail) { // Test is the sender is the same than the posted email field and if the mail has not been yet treated
                    if ($form->has('emailEmpty') && empty($form->get('emailEmpty')->getData())) { // Test HoneyPot
                        $messageController->msgsToWblist($partitionTag, $mailId, "W", MessageStatus::AUTHORIZED, 1, $rid); // 2 : authorized and 1 : captcha validate
                        $msgs->setValidateCaptcha(time());
                        $em->persist($msgs);
                        $em->flush();
                        $mailId = stream_get_contents($msgs->getMailId(), -1, 0);
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
        
        if (!$this->security->isGranted('ROLE_PREVIOUS_ADMIN')){
            $user = $this->getUser()->setPreferedLang($language);
            $em->persist($user);
            $em->flush();
        }
        


        $user = $this->getUser()->setPreferedLang($language);
        $em->persist($user);
        $em->flush();
        

        
        return new RedirectResponse($url);
    }

}
