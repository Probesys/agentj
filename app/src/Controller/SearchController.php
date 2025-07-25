<?php

namespace App\Controller;

use App\Entity\Msgs;
use App\Entity\User;
use App\Form\SearchFilterType;
use App\Repository\MsgrcptSearchRepository;
use App\Repository\OutMsgrcptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/advanced_search')]
class SearchController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MsgrcptSearchRepository $msgrcptSearchRepository,
        private OutMsgrcptRepository $outMsgrcptRepository
    ) {
    }

    #[Route(path: '/', name: 'advanced_search', methods: ['GET', 'POST'])]
    public function advancedSearch(
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $form = $this->createForm(SearchFilterType::class, [], ['method' => 'GET']);
        $form->handleRequest($request);

        $this->sanitizeSortParameter($request);

        $activeFilters = [];

        $activeFilters = $form->getData();

        $messageType = $activeFilters['messageType'] ?? 'incoming';
        if ($messageType == 'incoming') {
            $allMessages = $this->msgrcptSearchRepository
                ->getAdvancedSearchQuery($activeFilters);
        } else {
            $allMessages = $this->outMsgrcptRepository
                ->getAdvancedSearchQuery($activeFilters);
        }

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $recipients = $paginator->paginate(
            $allMessages,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'wrap-queries' => true,
                'fetchJoinCollection' => false,
                'distinct' => false,
                'defaultSortFieldName' => 'm.timeIso',
                'defaultSortDirection' => 'desc',
            ]
        );


        return $this->render('search/advanced_search.html.twig', [
            'messagesRecipients' => $recipients,
            'form' => $form->createView(),
            'messageType' => $messageType,
            'activeFilters' => $activeFilters,
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

    private function sanitizeSortParameter(Request $request): void
    {
        $allowedSortFields = [
            'mr.mailId',
            'm.fromAddr',
            'maddr.email',
            'm.subject',
            'm.timeIso',
            'mr.bspamLevel',
            'm.size',
            'm.host',
        ];

        $sort = $request->query->getString('sort', 'm.timeIso');

        if (!in_array($sort, $allowedSortFields, true)) {
            $request->query->set('sort', 'm.timeIso');
        }
    }
}
