<?php

namespace App\Controller;

use App\Repository\AlertRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashBoardController extends AbstractController
{
    #[Route('/dash/board', name: 'app_dash_board')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [

        ]);
    }

    #[Route('/dashboard/alerts', name: 'dashboard_alerts')]
    public function alerts(
        AlertRepository $alertRepository,
    ): Response
    {
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
}
