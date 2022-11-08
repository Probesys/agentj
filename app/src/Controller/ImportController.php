<?php

namespace App\Controller;

use App\Controller\Traits\ControllerCommonTrait;
use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\ImportType;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function dd;

/**
 * @Route("/admin/import")
 */
class ImportController extends AbstractController {

    use ControllerCommonTrait;
    use ControllerWBListTrait;

    private TranslatorInterface $translator;
    private EntityManagerInterface $em;
    private GroupService $groupService;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, GroupService $groupService) {
        $this->translator = $translator;
        $this->em = $em;
        $this->groupService = $groupService;
    }

    /**
     * @Route("/users", name="import_user_email", options={"expose"=true})
     */
    public function index(Request $request) {
        $translator = $this->translator;
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('import_user_email'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/csv") {
                $filename = 'import-agentj-' . time() . ".csv";
                $file = $fileUpload->move('/tmp/', $filename);
                $result = $this->import($file->getPathname());
                //delete file after import
                $fileSystem = new Filesystem();
                $fileSystem->remove([$file->getPathname()]);
                if ($result['user_import'] > 0) {
                    $message = $translator->trans('Generics.flash.ImportSuccess');
                    $message = str_replace('[NB_USER]', $result['user_import'], $message);
                    if (count($result['errors']) > 0) {
                        $warningMessage = $translator->trans('Generics.flash.ImportErrors', ['NB_ERRORS' => count($result['errors']), 'LIST_ROWS_ERROR' => implode(',', $result['errors'])]);
                        $this->addFlash('warning', $warningMessage);
                    }
                    $this->addFlash('success', $message);
                } else {
                    $this->addFlash('warning', 'Generics.flash.NoImport');
                }
                if ($result['user_already_exist'] > 0) {
                    //TODO
                }
            } else {
                $this->addFlash('danger', 'Generics.flash.BadFormatcsv');
            }
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
        }

        return $this->render('import/index.html.twig', [
                    'controller_name' => 'ImportController',
                    'form' => $form->createView(),
        ]);
    }

    /*
     *
     * Import user from file csv columns : email , LastName, FirstName, Group */

    function import($pathfile) {
        $split = ';';
        $nbUser = count(file($pathfile)) - 1;

        $row = 0;
        $import = 0;
        $already_exist = 0;
        $errors = [];
        $batchSize = 20;

        $em = $this->em;
        $groups = [];
        $emails = [];
        $groupWbUpdate = [];
        $domains = [];
        if (($handle = fopen($pathfile, "r")) !== false) {
            while (($data = fgetcsv($handle, 1024, $split)) !== false) {
                //check line nulber of columns
                if ((count($data) < 2)) {
                    $errors[] = $row + 1;
                    continue;
                }

                if (isset($data[0]) && $data[0] != "" && filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                    $email = $data[0];
                    $domainEmail = strtolower(substr($email, strpos($email, '@') + 1));
                    if (!isset($domains[$domainEmail])) {
                        $domains[$domainEmail]['entity'] = $em->getRepository(Domain::class)->findOneBy(['domain' => $domainEmail]);
                    }
                    //need domain exist in database
                    if ($domains[$domainEmail]['entity']) {
                        //group
                        $group = false;
                        if (isset($data[3]) && $data[3] != '') {
                            $slugGroup = $this->slugify($data[3]);
                            if (!isset($groups[$domainEmail][$slugGroup])) {
                                $group = $em->getRepository(Groups::class)->findOneBy(['domain' => $domains[$domainEmail]['entity'], 'name' => trim($data[3])]);
                                if (!$group) {
                                    //get rules of domain
                                    if (!isset($domains[$domainEmail]['wb'])) {
                                        $wb = $em->getRepository(Wblist::class)->searchByReceiptDomain('@' . $domainEmail);
                                        $domains[$domainEmail]['wb'] = $wb['wb'];
                                    }
                                    $group = new Groups();
                                    $group->setName($data[3]);
                                    $group->setDomain($domains[$domainEmail]['entity']);
                                    if ($domains[$domainEmail]['entity']->getPolicy()) {
                                        $group->setPolicy($domains[$domainEmail]['entity']->getPolicy());
                                    }
                                    if (isset($domains[$domainEmail]['wb'])) {
                                        $group->setWb($domains[$domainEmail]['wb']);
                                    }

                                    $em->persist($group);
                                    $em->flush();
                                    $groups[$domainEmail][$slugGroup] = $group;
                                } else {
                                    $groups[$domainEmail][$slugGroup] = $group;
                                }
                            } else {
                                $group = $groups[$domainEmail][$slugGroup];
                            }
                        }
                        if (!isset($emails[$email])) {
                            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                            if (!$user) {
                                $user = new User();
                            }
                            $user->setEmail($data[0]);
                            $user->setUsername($data[0]);
                            if (isset($data[1]) && isset($data[2])) {
                                $user->setFullname(trim($data[1] . " " . $data[2]));
                            }
                            $import++;
                            $user->setRoles('["ROLE_USER"]');
                            $domainEmail = strtolower(substr($data[0], strpos($data[0], '@') + 1));
                            $domain = $em->getRepository(Domain::class)->findOneBy(['domain' => $domainEmail]);
                            $user->setDomain($domain);
                            $em->persist($user);
                            $emails[$user->getEmail()] = $user->getEmail();
                            if ($group) {
                                $user->addGroup($group);
                                $em->persist($group);

                                $groupWbUpdate[$group->getid()] = $group->getid();
                            } else {
                                if ($domains[$domainEmail]['entity']->getPolicy()) {
                                    $user->setPolicy($domains[$domainEmail]['entity']->getPolicy());
                                }
                            }
                        }
                    } else {
                        //Todo add to $error[]
                    }
                    if ((($row % $batchSize) === 0) || ($row == $nbUser)) {
                        $em->flush();
                    }
                }

                $row++;
            }
            //Need to flush if user new is inferior $batchSize
            $em->flush();
            foreach ($groupWbUpdate as $groupIdUpdate) {
                $group = $em->getRepository(Groups::class)->find($groupIdUpdate);
                foreach ($group->getUsers() as $user) {
                    $this->groupService->updateWblistForUserAndAliases($user);
                }

            }
        }
        return ['user_import' => $import, 'errors' => $errors, 'user_already_exist' => $already_exist];
    }

}
