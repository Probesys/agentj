<?php

namespace App\Controller;

use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\ActionsFilterType;
use App\Form\ImportType;
use App\Entity\Domain;
use App\Repository\WblistRepository;
use App\Service\LogService;
use App\Service\Referrer;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class WblistController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        private Referrer $referrer,
    ) {
    }

    #[Route(path: '/wblist/{type}', name: 'wblist')]
    public function index(string $type, Request $request, PaginatorInterface $paginator): Response
    {
        if ($type !== 'W' && $type !== 'B') {
            throw $this->createNotFoundException("Type {$type} is invalid");
        }

        $actionLabel = $type === 'B' ? 'Message.Actions.Unlock' : 'Message.Actions.Delete';
        $filterForm = $this->createForm(ActionsFilterType::class, null, [
            'avalaibleActions' => [$actionLabel => 'delete'],
            'action' => $this->generateUrl('wblist_batch'),
        ]);

        $sortField = $request->query->getString('sortField');
        if (!in_array($sortField, ['emailuser', 'email', 'wb.datemod'])) {
            $sortField = 'email';
        }
        $sortDirection = $request->query->getString('sortDirection');
        if ($sortDirection !== 'asc' && $sortDirection !== 'desc') {
            $sortDirection = 'asc';
        }

        $query = trim($request->query->getString('search'));

        /** @var User $user */
        $user = $this->getUser();
        $title = '';
        $wblist = $this->em->getRepository(Wblist::class)->search($type, $user, $query, [
            'field' => $sortField,
            'direction' => $sortDirection,
        ]);

        switch ($type) {
            case "W":
                $title = $this->translator->trans('Navigation.whitelist');
                break;
            case "B":
                $title = $this->translator->trans('Navigation.blacklist');
                break;
        }
        $totalItemFound = count($wblist);

        // Retrieve perPage from the request or use the default value
        $perPage = $request->query->getInt('per_page', (int) $this->getParameter('app.per_page_global'));

        // Set the initial value of perPage in the form
        $filterForm->get('per_page')->setData($perPage);

        $wblist2Show = $paginator->paginate(
            $wblist,
            $request->query->getInt('page', 1)/* page number */,
            $perPage
        );
        return $this->render('wb_list/index.html.twig', [
                    'controller_name' => 'WBListController',
                    'wblist' => $wblist2Show,
                    'wbTypeList' => $type,
                    'title' => $title,
                    'totalItemFound' => $totalItemFound,
                    'filter_form' => $filterForm->createView()
        ]);
    }


    #[Route(path: '/WBlist/{rid}/{sid}/{priority}/delete', name: 'wblist_delete', methods: 'GET')]
    public function deleteAction(
        int $rid,
        int $sid,
        int $priority,
        WblistRepository $wblistRepository,
        Request $request
    ): RedirectResponse {
        if ($this->isCsrfTokenValid('delete_wblist' . $rid . $sid, $request->query->get('_token'))) {
            $this->deleteWbList($rid, $sid, $priority, $wblistRepository);
            $this->addFlash('success', $this->translator->trans('Message.Flash.deleteSuccesFull'));
        } else {
            $this->addFlash('error', 'Invalid csrf token');
        }

        return new RedirectResponse($this->referrer->get());
    }

    private function deleteWbList(
        int $rid,
        int $sid,
        int $priority,
        WblistRepository $wblistRepository,
    ): void {

        $mainUser = $this->em->getRepository(User::class)->find($rid);
        $userAndAliases = [];

        // if adress in an alias we get the target mail
        if ($mainUser && $mainUser->getOriginalUser()) {
            $mainUser = $mainUser->getOriginalUser();
        }

        // we check if aliases exist
        if ($mainUser) {
            $userAndAliases = $this->em->getRepository(User::class)->findBy(['originalUser' => $mainUser->getId()]);
            array_unshift($userAndAliases, $mainUser);
        }

        foreach ($userAndAliases as $user) {
            $wblistRepository->delete($user->getId(), $sid, $priority);
        }
    }

    #[Route(
        path: '/WBlist/batch/delete/',
        name: 'wblist_batch_delete',
        methods: 'POST',
        options: ['expose' => true],
    )]
    public function batchDeleteAction(Request $request): RedirectResponse
    {

        foreach ($request->request->all('id') as $obj) {
            $mailInfo = json_decode($obj);
            $this->em->getRepository(Wblist::class)->delete($mailInfo[0], $mailInfo[1], $mailInfo[2]);
        }

        return new RedirectResponse($this->referrer->get());
    }


    #[Route(
        path: '/batch/{action}',
        name: 'wblist_batch',
        methods: 'POST',
        options: ['expose' => true],
    )]
    public function batchWbListAction(Request $request, ?string $action = null): RedirectResponse
    {
        $em = $this->em;
        if ($action) {
            $logService = new LogService($em);
            foreach ($request->request->all('id') as $obj) {
                $mailInfo = json_decode($obj);
                switch ($action) {
                    case 'delete':
                        $this->deleteWbList(
                            $mailInfo[0],
                            $mailInfo[1],
                            $mailInfo[2],
                            $em->getRepository(Wblist::class)
                        );
                        $logService->addLog('delete batch wblist', $mailInfo[1]);
                        break;
                }
            }
        }

        return new RedirectResponse($this->referrer->get());
    }

    #[Route(path: '/wblist/admin/import', name: 'import_wblist', options: ['expose' => true])]
    public function importWbListAction(Request $request): Response
    {
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('import_wblist'),
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/plain") {
                $filename = 'import-wblist-agentj-' . time() . ".txt";
                $file = $fileUpload->move('/tmp/', $filename);
                $this->importWbList($file->getPathname(), $form->get('domain')->getData());
            } else {
                $this->addFlash('danger', 'Generics.flash.BadFormatcsv');
            }

            return new RedirectResponse($this->referrer->get());
        }
        return $this->render('import/index_wblist.html.twig', [
                    'controller_name' => 'ImportController',
                    'form' => $form,
        ]);
    }

    private function importWbList(string $pathfile, Domain $domain): void
    {
        $tabWblist = [];
        if (($handle = fopen($pathfile, "r"))) {
            while (($data = fgets($handle, 4096)) !== false) {
                $data = $this->sanitizeImportedData($data);

                if ($data === null) {
                    continue;
                }

                $mailaddrSender = $this->em->getRepository(Mailaddr::class)->findOneBy(['email' => $data]);
                //if email doesn't exist then we create email in Mailaddr
                if (!$mailaddrSender) {
                    $mailaddrSender = new Mailaddr();
                    $mailaddrSender->setEmail($data);
                    $mailaddrSender->setPriority(6);
                    $this->em->persist($mailaddrSender);
                    $this->em->flush();
                }

                if (
                    isset($tabWblist[$domain->getId()]) &&
                    in_array($mailaddrSender->getId(), $tabWblist[$domain->getId()])
                ) {
                    continue;
                }

                $user = $this->em->getRepository(User::class)->findOneBy(['email' =>  '@' . $domain->getDomain()]);
                $wblist = $this->em->getRepository(Wblist::class)->findOneBy([
                    'sid' => $mailaddrSender,
                    'rid' => $user,
                ]);
                if (!$wblist) {
                    $wblist = new Wblist($user, $mailaddrSender);
                }

                $wblist->setWb('W');
                $wblist->setPriority(Wblist::WBLIST_PRIORITY_USER);
                $wblist->setType(Wblist::WBLIST_TYPE_IMPORT);
                $this->em->persist($wblist);
                $tabWblist[$domain->getId()][] = $mailaddrSender->getId();
            }
            $this->em->flush();
        }
    }

    private function sanitizeImportedData(string $data): ?string
    {
        $data = trim($data);

        $email = filter_var($data, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
        if ($email !== false) {
            return $email;
        }

        // This allows domains to be imported in both formats: "example.org" and "@example.org".
        if (str_starts_with($data, '@')) {
            $data = substr($data, 1);
        }

        $domain = filter_var($data, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        if ($domain !== false) {
            return '@' . $domain;
        }

        return null;
    }
}
