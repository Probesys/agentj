<?php

namespace App\Controller;

use App\Entity\Mailaddr;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\ActionsFilterType;
use App\Form\ImportType;
use App\Repository\MsgsRepository;
use App\Repository\WblistRepository;
use App\Service\LogService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WblistController extends AbstractController
{

    private $translator;

    public function __construct(TranslatorInterface $translator)
    {

        $this->translator = $translator;
    }

  /**
   * @Route("/wblist/{type}", name="wblist")
   */
    public function index($type, Request $request, PaginatorInterface $paginator)
    {

        $sortParams = [];
        $filterForm = $this->createForm(ActionsFilterType::class, null, ['avalaibleActions' => ['Message.Actions.Delete' => 'delete'], 'action' => $this->generateUrl('wblist_batch')]);

        if ($request->query->has('sortDirection') && $request->query->has('sortField')) {
            $sortParams['sort'] = $request->query->get('sortField');
            $sortParams['direction'] = $request->query->get('sortDirection');
        }
        $searchKey = null;
        if ($request->query->has('search')) {
            $searchKey = trim($request->query->get('search'));
        }

        $wblist = $this->getDoctrine()->getManager()->getRepository(Wblist::class)->search($type, $this->getUser(), $searchKey, $sortParams);
        switch ($type) {
            case "W":
                $title = $this->translator->trans('Navigation.whitelist');
                break;
            case "B":
                $title = $this->translator->trans('Navigation.blacklist');
                break;
        }
        $totalItemFound = count($wblist);
        $per_page = $this->getParameter('app.per_page_global');
        $wblist2Show = $paginator->paginate(
            $wblist,
            $request->query->getInt('page', 1)/* page number */,
            $request->query->getInt('per_page', $per_page)
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

  /**
   * @param integer $id
   * @Route("/WBlist/{rid}/{sid}/delete", name="wblist_delete",  methods="GET")
   *
   * @return Response
   */
    public function deleteAction($rid, $sid, WblistRepository $WblistRepository, Request $request)
    {
        if ($this->isCsrfTokenValid('delete_wblist' . $rid . $sid, $request->query->get('_token'))) {
            $this->deleteWbList($rid, $sid, $WblistRepository);
            $this->addFlash('success', $this->translator->trans('Message.Flash.deleteSuccesFull'));
        } else {
            $this->addFlash('error', 'Invalid csrf token');
        }

        if (!empty($request->headers->get('referer'))) {
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $this->redirectToRoute("message");
        }
    }

    private function deleteWbList($rid, $sid, WblistRepository $WblistRepository)
    {

        $mainUser = $this->getDoctrine()->getRepository(User::class)->find($rid);

      // if adress in an alias we get the target mail
        if ($mainUser && $mainUser->getOriginalUser()) {
            $mainUser = $mainUser->getOriginalUser();
        }

      // we check if aliases exist
        if ($mainUser) {
            $userAndAliases = $this->getDoctrine()->getRepository(User::class)->findBy(['originalUser' => $mainUser->getId()]);
            array_unshift($userAndAliases, $mainUser);
        }

        foreach ($userAndAliases as $user) {
            $WblistRepository->delete($user->getId(), $sid);
        }
    }

  /**
     *
     * @Route("/WBlist/batch/delete/", name="wblist_batch_delete",  methods="POST" , options={"expose"=true})
     * @return Response
     */
    public function batchDeleteAction(Request $request, MsgsRepository $msgRepository)
    {
        $em = $this->getDoctrine()->getManager();
        
        foreach ($request->request->get('id') as $obj) {
            $mailInfo = json_decode($obj);
            $em->getRepository(Wblist::class)->delete($mailInfo[0], $mailInfo[1]);
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

  /**
     *
     * @Route("/batch/{action}", name="wblist_batch",  methods="POST" , options={"expose"=true})
     * @return Response
     */
    public function batchWbListAction($action = null, Request $request, MsgsRepository $msgRepository)
    {
        $em = $this->getDoctrine()->getManager();
        if ($action) {
            $logService = new LogService($em);
            foreach ($request->request->get('id') as $obj) {
                $mailInfo = json_decode($obj);
                switch ($action) {
                    case 'delete':
                          $mailInfo = json_decode($obj);
                          $this->deleteWbList($mailInfo[0], $mailInfo[1], $em->getRepository(Wblist::class));
              //            $em->getRepository(Wblist::class)->deleteMessage($mailInfo[0], $mailInfo[1]);
                          $logService->addLog('delete batch wblist', $mailInfo[1]);
                        break;
                }
            }
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

  /**
   * @Route("/wblist/admin/import", name="import_wblist" , options={"expose"=true})
   */
    public function importWbListAction(Request $request)
    {
        $form = $this->createForm(ImportType::class, null, [
        'action' => $this->generateUrl('import_wblist'),
        'user' => $this->getUser()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/csv") {
                $filename = 'import-wblist-agentj-' . time() . ".csv";
                $file = $fileUpload->move('/tmp/', $filename);

                $users = [];
                if ($form->has('domains')) {
                    $domainIds = array_map(function ($entity) {
                        return $entity->getId();
                    }, $form->get('domains')->getData()->toArray());

                    $users = $this->getDoctrine()->getRepository(User::class)->findBy(['domain' => $domainIds, 'originalUser' => null]);
                } else {
                    $users[] = $this->getUser();
                }
                $result = $this->importWbList($file->getPathname(), $users);
            }
        }
        return $this->render('import/index_wblist.html.twig', [
                'controller_name' => 'ImportController',
                'form' => $form->createView(),
        ]);
    }

  /**
   * Import email adresses to whiteliste of $users
   * @param type $pathfile
   * @param type $users
   */
    private function importWbList($pathfile, $users)
    {
        $em = $this->getDoctrine()->getManager();
        if (($handle = fopen($pathfile, "r")) !== false) {
            while (($data = fgets($handle, 4096)) !== false) {
                $mailaddrSender = $em->getRepository(Mailaddr::class)->findOneBy(['email' => trim($data)]);
                //if email doesn't exist then we create email in Mailaddr
                if (!$mailaddrSender) {
                    $mailaddrSender = new Mailaddr();
                    $mailaddrSender->setEmail(trim($data));
                    $mailaddrSender->setPriority(6);
                    $em->persist($mailaddrSender);
                }

                if (filter_var(trim($data), FILTER_VALIDATE_EMAIL)) {
                    foreach ($users as $user) {
                        $wblist = $em->getRepository(Wblist::class)->findOneBy(['sid' => $mailaddrSender, 'rid' => $users]);
                        if (!$wblist) {
                                $wblist = new Wblist($user, $mailaddrSender);
                        }
                        $wblist->setWb('W');
                        $wblist->setPriority(Wblist::WBLIST_PRIORITY_USER);
                        $wblist->setType(1);
                        $em->persist($wblist);

        //            dump($wblist);
                        echo stream_get_contents($user->getEmail(), -1, 0) . "<br>";
                    }
                }
            }
            $em->flush();
        }
    }
}
