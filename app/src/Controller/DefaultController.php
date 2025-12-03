<?php

namespace App\Controller;

use App\Amavis\ContentType;
use App\Amavis\MessageStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MsgrcptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    #[Route(path: '/', name: 'homepage')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $showAlertWidget = true;
        if ($user->getDomain() && $user->getDomain()->getSendUserAlerts() == false) {
            $showAlertWidget = false;
        }

        return $this->render('home/index.html.twig', [
                    'controller_name' => 'DefaultController',
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

    #[Route('/per_page/{nbItems}', name: 'per_page')]
    public function perPage(int $nbItems, Request $request): Response
    {
        $referer = $request->headers->get('referer');
        $session = $request->getSession();
        $session->set('perPage', $nbItems);
        $urlParts = parse_url($referer);
        parse_str($urlParts['query'] ?? '', $query);
        $query['page'] = 1;
        $newQuery = http_build_query($query);
        $newReferer = strtok($referer, '?') . '?' . $newQuery;

        return $referer ? $this->redirect($newReferer) : $this->redirectToRoute('homepage');
    }
}
