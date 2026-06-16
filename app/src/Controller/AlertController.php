<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlertController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/alert/read/{id}', name: 'alert_read')]
    public function read(Alert $alert, EntityManagerInterface $entityManager): Response
    {
        $alert->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('homepage');
    }

    #[Route('/alert/delete/{id}', name: 'alert_delete', methods: 'POST')]
    public function delete(Alert $alert, EntityManagerInterface $entityManager, Request $request): Response
    {
        $csrfToken = $request->request->getString('_token', '');

        if (!$this->isCsrfTokenValid('delete', $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('Generics.flash.invalidCsrfToken'));
            return $this->redirectToRoute('alert_index');
        }

        $entityManager->remove($alert);
        $entityManager->flush();

        return $this->redirectToRoute('alert_index');
    }

    #[Route('/alerts', name: 'alert_index')]
    public function index(
        AlertRepository $alertRepository,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $searchKey = $request->query->getString('search', '');

        $alertQuery = $alertRepository->getUserSearchQuery(
            user: $user,
            searchKey: $searchKey
        );

        $perPage = (int) $this->getParameter('app.per_page_global');
        $perPage = $request->getSession()->has('perPage') ? $request->getSession()->get('perPage') : $perPage;

        $alerts = $paginator->paginate(
            $alertQuery,
            $request->query->getInt('page', 1),
            $perPage,
            [
                'defaultSortFieldName' => 'a.date',
                'defaultSortDirection' => 'asc',
            ]
        );

        return $this->render('alert/index.html.twig', [
            'alerts' => $alerts,
        ]);
    }
}
