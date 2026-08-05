<?php

namespace App\Controller;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\AlertRepository;
use App\Repository\DomainRepository;
use App\Repository\LimitReportRepository;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRecipientSearchRepository;
use App\Repository\OutMessageRecipientRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

final class DashboardController extends AbstractController
{
    public function __construct(
        private MessageRecipientSearchRepository $messageRecipientSearchRepository,
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

        return $this->render('dashboard/alerts.html.twig', [
            'alerts' => $alerts,
            'unreadAlertsCount' => $unreadAlertsCount,
        ]);
    }

    #[Route('/dashboard/stats-by-status', name: 'dashboard_stats_by_status')]
    public function statsByStatus(): Response
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $latestMessageRecipients = $this->messageRecipientSearchRepository
            ->getSearchQuery($user)
            ->setMaxResults(5)
            ->getResult();

        $stats['untreated'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::UNTREATED,
        );
        $stats['authorized'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::AUTHORIZED,
        );
        $stats['banned'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::BANNED,
        );
        $stats['delete'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::DELETED,
        );
        $stats['restored'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::RESTORED,
        );
        $stats['error'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::ERROR,
        );
        $stats['spammed'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::SPAMMED,
        );
        $stats['virus'] = $this->messageRecipientSearchRepository->countByType(
            $user,
            MessageStatus::VIRUS,
        );

        return $this->render('dashboard/stats-by-status.html.twig', [
            'stats' => $stats,
            'latestMessageRecipients' => $latestMessageRecipients,
        ]);
    }

    #[Route('/dashboard/stats-by-days', name: 'dashboard_stats_by_days')]
    public function statsByDays(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $nbUntreadtedMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::UNTREATED
        );
        $nbAutorizeMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::AUTHORIZED
        );
        $nbBannedMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::BANNED
        );
        $nbDeletedMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::DELETED
        );
        $nbRestoredMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::RESTORED
        );
        $nbErrorMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::ERROR
        );
        $nbSpammedMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::SPAMMED
        );
        $nbVirusMsgByDay = $this->messageRecipientSearchRepository->countByTypeAndDays(
            user: $user,
            messageStatus: MessageStatus::VIRUS
        );

        return $this->render('dashboard/stats-by-days.html.twig', [
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
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $domains = $domainRepository->findAll();
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $domains = $user->getDomains();
        } else {
            $domains = [];
        }

        $users = [];
        $data = [];
        $dataAll = [
            'totalMsgCount' => 0,
            'totalMsgBlockedCount' => 0,
            'totalOutMsgCount' => 0,
            'totalOutMsgBlockedCount' => 0,
        ];

        $searchKey = $request->query->getString('search', '');

        foreach ($domains as $domain) {
            $domainUsers = $userRepository->getUsersWithMessageCountsByDomain($domain, $searchKey);
            $users = array_merge($users, $domainUsers);

            $totalMsgCount = array_reduce($domainUsers, function ($carry, $user) {
                return $carry + $user['msgCount'];
            }, 0);
            $totalMsgBlockedCount = array_reduce($domainUsers, function ($carry, $user) {
                return $carry + $user['msgBlockedCount'];
            }, 0);
            $totalOutMsgCount = array_reduce($domainUsers, function ($carry, $user) {
                return $carry + $user['outMsgCount'];
            }, 0);
            $totalOutMsgBlockedCount = array_reduce($domainUsers, function ($carry, $user) {
                return $carry + $user['outMsgBlockedCount'];
            }, 0);

            $data[$domain->getId()] = [
                'totalMsgCount' => $totalMsgCount,
                'totalMsgBlockedCount' => $totalMsgBlockedCount,
                'totalOutMsgCount' => $totalOutMsgCount,
                'totalOutMsgBlockedCount' => $totalOutMsgBlockedCount,
            ];

            $dataAll['totalMsgCount'] += $totalMsgCount;
            $dataAll['totalMsgBlockedCount'] += $totalMsgBlockedCount;
            $dataAll['totalOutMsgCount'] += $totalOutMsgCount;
            $dataAll['totalOutMsgBlockedCount'] += $totalOutMsgBlockedCount;
        }

        $data['All'] = $dataAll;

        $isPreview = $request->query->getBoolean('preview');

        if ($isPreview) {
            $perPage = 5;
        } else {
            $perPage = (int) $this->getParameter('app.per_page_global');
            $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;
        }

        $paginatedUsers = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            $perPage,
        );

        if ($isPreview) {
            $template = 'dashboard/stats-by-in-out.html.twig';
        } else {
            $template = 'dashboard/stats-by-in-out-list.html.twig';
        }

        return $this->render($template, [
            'domains' => $domains,
            'users' => $paginatedUsers,
            'data' => $data,
        ]);
    }

    #[Route(path: '{email}/messages_stats/', name: 'messages_stats', methods: 'GET')]
    public function showMessagesStats(
        User $user,
        MessageRecipientRepository $messageRecipientRepository,
        OutMessageRecipientRepository $outMessageRecipientRepository,
        LimitReportRepository $limitReportRepository,
    ): Response {
        $messages = array_merge(
            $messageRecipientRepository->findByEmailRecipient($user),
            $outMessageRecipientRepository->findByEmailSender($user),
        );

        $statusCounts = [];

        foreach ($messages as $message) {
            if ($message->isAuthorized() || $message->isRestored()) {
                continue;
            }

            $status = $message->getStatusName();
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = [
                    'label' => $message->getStatusLabel(),
                    'quantity' => 0,
                    'quantityOut' => 0,
                ];
            }

            if ($message instanceof MessageRecipient) {
                $statusCounts[$status]['quantity']++;
            } else {
                $statusCounts[$status]['quantityOut']++;
            }
        }

        // Add limit reports to the list as mails blocked because of quota
        // aren't saved as "out messages", but we still wan't to count them.
        $countLimitReports = $limitReportRepository->countByUser($user);
        if ($countLimitReports > 0) {
            $statusCounts['quota'] = [
                'label' => new TranslatableMessage('Entities.Message.Quota'),
                'quantity' => 0,
                'quantityOut' => $countLimitReports,
            ];
        }

        return $this->render('dashboard/messages_stats.html.twig', [
            'statusCounts' => $statusCounts,
        ]);
    }
}
