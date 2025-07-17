<?php

namespace App\Controller;

use App\Entity\Msgs;
use App\Entity\User;
use App\Form\SearchFilterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/advanced_search')]
class SearchController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/', name: 'advanced_search', methods: ['GET', 'POST'])]
    public function advancedSearch(Request $request): Response
    {
        $form = $this->createForm(SearchFilterType::class);
        $form->handleRequest($request);

        $data = $form->getData();
        $messageType = $data['messageType'] ?? 'incoming';

        $sortParams = null;
        if ($request->request->has('sortField') && $request->request->has('sortDirection')) {
            $sortParams = [
                'sort' => $request->request->getString('sortField'),
                'direction' => $request->request->getString('sortDirection'),
            ];
        }

        /** @var User $user */
        $user = $this->getUser();
        $allMessages = $this->em->getRepository(Msgs::class)->advancedSearch($user, $messageType, $sortParams);


        // Initialize active filters
        $activeFilters = [];

        // If form is submitted and valid, set active filters and filter messages
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Track which filters are active
            foreach ($data as $key => $value) {
                if (!empty($value)) {
                    $activeFilters[$key] = true;
                }
            }

            // Apply all active filters to the messages
            $allMessages = array_filter($allMessages, function ($message) use ($data) {
                // Basic filters
                if (!empty($data['fromAddr']) && stripos($message['from_addr'], $data['fromAddr']) === false) {
                    return false;
                }
                if (!empty($data['email']) && stripos($message['email'], $data['email']) === false) {
                    return false;
                }
                if (!empty($data['subject']) && stripos($message['subject'], $data['subject']) === false) {
                    return false;
                }
                if (!empty($data['mailId']) && stripos($message['mail_id'], $data['mailId']) === false) {
                    return false;
                }

                // Advanced filters
                if ($data['bspamLevelMin'] !== null && $message['bspam_level'] < $data['bspamLevelMin']) {
                    return false;
                }
                if ($data['bspamLevelMax'] !== null && $message['bspam_level'] > $data['bspamLevelMax']) {
                    return false;
                }
                if (!empty($data['startDate']) && $message['time_iso'] < $data['startDate']->format('Ymd\THis\Z')) {
                    return false;
                }
                if (!empty($data['endDate']) && $message['time_iso'] > $data['endDate']->format('Ymd\THis\Z')) {
                    return false;
                }
                if (!empty($data['size']) && stripos($message['size'], $data['size']) === false) {
                    return false;
                }
                if (!empty($data['host']) && stripos($message['host'], $data['host']) === false) {
                    return false;
                }
                if (!empty($data['replyTo']) && $message['replyTo'] !== $data['replyTo']) {
                    return false;
                }
                return true;
            });
        }

        // Handle AJAX requests
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'content' => $this->renderView('search/_messages.html.twig', [
                    'msgs' => $allMessages,
                    'messageType' => $messageType,
                    'activeFilters' => $activeFilters,
                ]),
            ]);
        }

        // Render the main template
        return $this->render('search/advanced_search.html.twig', [
            'msgs' => $allMessages,
            'form' => $form->createView(),
            'messageType' => $messageType,
            'activeFilters' => $activeFilters, // Pass the activeFilters to the main view
        ]);
    }

    #[Route(path: '/{partitionTag}/{mailId}/{rid}/show/', name: 'out_message_show', methods: ['GET'])]
    public function showAction(int $partitionTag, string $mailId, int $rid): Response
    {
        $conn = $this->em->getConnection();

        // Fetching the OutMsgrcpt data using raw SQL
        $sqlOutMsgrcpt = <<<SQL
            SELECT * FROM out_msgrcpt
            WHERE partition_tag = :partitionTag
            AND mail_id = :mailId
            AND rid = :rid
        SQL;

        // Execute the query and fetch data as an associative array
        $stmtOutMsgrcpt = $conn->executeQuery($sqlOutMsgrcpt, [
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
            'rid' => $rid,
        ]);
        $outMsgRcpt = $stmtOutMsgrcpt->fetchAssociative();

        // Fetching the OutMsgs data using raw SQL
        $sqlOutMsgs = <<<SQL
            SELECT * FROM out_msgs
            WHERE partition_tag = :partitionTag
            AND mail_id = :mailId
        SQL;

        // Execute the query and fetch data as an associative array
        $stmtOutMsgs = $conn->executeQuery($sqlOutMsgs, [
            'partitionTag' => $partitionTag,
            'mailId' => $mailId,
        ]);
        $outMsg = $stmtOutMsgs->fetchAssociative();

        /** @var User $user */
        $user = $this->getUser();
        $allMessages = $this->em->getRepository(Msgs::class)->advancedSearch($user, 'outgoing');

        $ms = array_filter($allMessages, function ($msg) use ($outMsg) {
            return $outMsg !== false && $msg['mail_id'] === $outMsg['mail_id'];
        });

        return $this->render('message/out_show.html.twig', [
            'controller_name' => 'MessageController',
            'outMsg' => $outMsg,
            'outMsgRcpt' => $outMsgRcpt,
            'message' => $ms,
        ]);
    }
}
