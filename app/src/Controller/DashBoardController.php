<?php

namespace App\Controller;

use App\Amavis\MessageStatus;
use App\Repository\AlertRepository;
use App\Repository\DomainRepository;
use App\Repository\MsgrcptSearchRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashBoardController extends AbstractController
{
    public function __construct(
        private MsgrcptSearchRepository $msgrcptSearchRepository,
    ) {
    }

    #[Route('/dashboard/alerts', name: 'dashboard_alerts')]
    public function alerts(
        AlertRepository $alertRepository,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $alerts = $alertRepository->findBy(['user' => $user->getId()], ['date' => 'DESC'], 5);
        $allAlerts = $alertRepository->findBy(['user' => $user->getId()], ['date' => 'DESC']);

        $unreadAlertsCount = count(array_filter($allAlerts, function ($alert) {
            return !$alert->getIsRead();
        }));

        return $this->render('home/alerts.html.twig', [
            'alerts' => $alerts,
            'unreadAlertsCount' => $unreadAlertsCount,
        ]);
    }

    #[Route('/dashboard/stats-by-status', name: 'dashboard_stats_by_status')]
    public function statsByStatus(): Response
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $latestMessageRecipients = $this->msgrcptSearchRepository
            ->getSearchQuery($user)
            ->setMaxResults(5)
            ->getResult();

        $msgs['untreated'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::UNTREATED);
        $msgs['authorized'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
        $msgs['banned'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::BANNED);
        $msgs['delete'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::DELETED);
        $msgs['restored'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::RESTORED);
        $msgs['error'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::ERROR);
        $msgs['spammed'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::SPAMMED);
        $msgs['virus'] = $this->msgrcptSearchRepository->countByType($user, MessageStatus::VIRUS);

        return $this->render('home/stats-by-status.html.twig', [
            'msgs' => $msgs,
            'latestMessageRecipients' => $latestMessageRecipients,
        ]);
    }

    #[Route('/dashboard/stats-by-days', name: 'dashboard_stats_by_days')]
    public function statsByDays(): Response
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

        return $this->render('home/stats-by-days.html.twig', [
            'nbUntreadtedMsgByDay' => $nbUntreadtedMsgByDay,
            'nbAutorizeMsgByDay' => $nbAutorizeMsgByDay,
            'nbBannedMsgByDay' => $nbBannedMsgByDay,
            'nbDeletedMsgByDay' => $nbDeletedMsgByDay,
            'nbRestoredMsgByDay' => $nbRestoredMsgByDay,
            'nbErrorMsgByDay' => $nbErrorMsgByDay,
            'nbSpammedMsgByDay' => $nbSpammedMsgByDay,
            'nbVirusMsgByDay' => $nbVirusMsgByDay,
        ]);
    }

    #[Route('/dashboard/stats-by-in-out', name: 'dashboard_stats_by_in_out')]
    public function statsByInOut(
        DomainRepository $domainRepository,
        UserRepository $userRepository,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $latestMessageRecipients = $this->msgrcptSearchRepository
            ->getSearchQuery($user)
            ->setMaxResults(5)
            ->getResult();



        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $domains = $domainRepository->findAll();
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $domains = $user->getDomains();
        } else {
            $domains = [];
        }

        foreach ($domains as $domain) {
            $users = $userRepository->getUsersWithRoleAndMessageCounts($user, $domain);

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
        $users = $userRepository->getUsersWithRoleAndMessageCounts($user);

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

        return $this->render('home/stats-by-in-out.html.twig', [
            'latestMessageRecipients' => $latestMessageRecipients,
            'domains' => $domains,
            'users' => $users,
            'data' => $data,
        ]);
    }
}
