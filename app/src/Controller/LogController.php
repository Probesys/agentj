<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\User;
use App\Form\LogFilterType;
use App\Repository\DomainRepository;
use App\Repository\LogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class LogController extends AbstractController
{
    public function __construct(
        private LogRepository $logRepository,
        private DomainRepository $domainRepository,
    ) {
    }

    #[Route(path: '/logs', name: 'log_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(LogFilterType::class, null, [
            'domains' => $this->domainRepository->findAllowedForUser($user),
        ]);
        $form->handleRequest($request);

        $filters = $form->isSubmitted() && $form->isValid() ? $form->getData() : [];

        $this->sanitizeSortParameter($request);

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $logs = $paginator->paginate(
            $this->logRepository->getSearchQuery($user, $filters),
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'l.created',
                'defaultSortDirection' => 'desc',
            ]
        );

        return $this->render('log/index.html.twig', [
            'logs' => $logs,
            'form' => $form,
        ]);
    }

    #[IsGranted('DOMAIN_ACCESS', subject: 'log')]
    #[Route(path: '/logs/{id}', name: 'log_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Log $log): Response
    {
        return $this->render('log/show.html.twig', [
            'log' => $log,
        ]);
    }

    private function sanitizeSortParameter(Request $request): void
    {
        $allowedSortFields = [
            'l.id',
            'l.created',
            'u.username',
            'l.action',
            'l.mailId',
            'd.domain',
        ];

        $sort = $request->query->getString('sort', 'l.created');

        if (!in_array($sort, $allowedSortFields, true)) {
            $request->query->set('sort', 'l.created');
        }
    }
}
