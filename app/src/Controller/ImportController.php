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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function dd;

#[Route(path: '/admin/import')]
class ImportController extends AbstractController
{
    use ControllerCommonTrait;
    use ControllerWBListTrait;

    private TranslatorInterface $translator;
    private EntityManagerInterface $em;
    private GroupService $groupService;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, GroupService $groupService)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->groupService = $groupService;
    }

    #[Route(path: '/users', name: 'import_user_email', options: ['expose' => true])]
    public function index(Request $request): Response
    {
        $translator = $this->translator;
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('import_user_email'),
        ]);
        $form->remove('domains');

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
                    $message = str_replace('[NB_USER]', strval($result['user_import']), $message);
                    $this->addFlash('success', $message);
                } else {
                    $this->addFlash('warning', 'Generics.flash.NoImport');
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

    /**
     * Import user from file csv columns : email , LastName, FirstName, Group
     *
     * @return array{user_import: integer}
     */
    private function import(string $pathfile): array
    {
        $translator = $this->translator;
        $split = ';';

        $lines = file($pathfile);
        if ($lines === false) {
            return ['user_import' => 0];
        }

        $nbUser = count($lines) - 1;

        $row = 0;
        $import = 0;
        $alreadyExist = [];
        $errors = [];
        $batchSize = 20;

        $em = $this->em;
        $groups = [];
        $emails = [];
        $groupWbUpdate = [];
        $domains = [];
        try {
            if (($handle = fopen($pathfile, "r")) !== false) {
                while (($data = fgetcsv($handle, 1024, $split)) !== false) {
                    //check line number of columns, if empty
                    if ((count($data) != 4)) {
                        $errors[] = $translator->trans('Generics.flash.IncorrectColumns', ['ROW' => $row + 1]);
                        continue;
                    }

                    if (isset($data[0]) && $data[0] != "" && filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                        $email = $data[0];
                        $domainEmail = strtolower(substr($email, strpos($email, '@') + 1));
                        if (!isset($domains[$domainEmail])) {
                            $domains[$domainEmail]['entity'] = $em->getRepository(Domain::class)->findOneBy([
                                'domain' => $domainEmail,
                            ]);
                        }
                        //need domain exist in database
                        if ($domains[$domainEmail]['entity']) {
                            //group
                            $group = false;
                            if (isset($data[3]) && $data[3] != '') {
                                $slugGroup = $this->slugify($data[3]);
                                if (!isset($groups[$domainEmail][$slugGroup])) {
                                    $group = $em->getRepository(Groups::class)->findOneBy([
                                        'domain' => $domains[$domainEmail]['entity'],
                                        'name' => trim($data[3]),
                                    ]);
                                    if (!$group) {
                                        //get rules of domain
                                        if (!isset($domains[$domainEmail]['wb'])) {
                                            $wblist = $em->getRepository(Wblist::class)
                                                         ->findOneByRecipientDomain($domains[$domainEmail]['entity']);

                                            if ($wblist === null) {
                                                $errors[] = 'No wblist found for this domain ' . $domainEmail;
                                                continue;
                                            }
                                            $domains[$domainEmail]['wb'] = $wblist->getWb();
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
                                } else {
                                    $alreadyExist[] = $email;
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
                                if ($domain && $domain->getPolicy()) {
                                    $user->setPolicy($domain->getPolicy());
                                }
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
                            $errors[] = $translator->trans(
                                'Generics.flash.NonexistantDomain',
                                ['ROW' => $row + 1, 'DOMAIN' => $domainEmail],
                            );
                        }
                        if ((($row % $batchSize) === 0) || ($row == $nbUser)) {
                            $em->flush();
                        }
                    } else {
                        $errors[] =  $translator->trans('Generics.flash.InvalidEmail', ['ROW' => $row + 1]);
                    }

                    $row++;
                }

                //Need to flush if user new is inferior $batchSize
                $em->flush();
                $this->groupService->updateWblist();
            }
        } catch (\Exception $e) {
            $dangerMessage = $translator->trans('Generics.flash.ImportError', ['ERROR_MESSAGE' => $e->getMessage()]);
            $this->addFlash('danger', $dangerMessage);
        }

        if (count($errors) > 0) {
            $dangerMessageList = $translator->trans(
                'Generics.flash.ImportErrorsList',
                ['LIST_ROWS_ERROR' => implode('<br> ', $errors)],
            );
            $this->addFlash('danger', $dangerMessageList);
        }

        if (count($alreadyExist) > 0) {
            $warningMessage = $translator->trans(
                'Generics.flash.ImportExisting',
                ['LIST_DUPES' => implode(', ', $alreadyExist)],
            );
            $this->addFlash('warning', $warningMessage);
        }

        return ['user_import' => $import];
    }

    #[Route(path: '/users_alias', name: 'import_user_alias', options: ['expose' => true])]
    public function indexAlias(Request $request): Response
    {
        $translator = $this->translator;
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('import_user_alias'),
        ]);
        $form->remove('domains');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileUpload = $form['attachment']->getData();
            if ($fileUpload->getClientMimeType() == "text/csv") {
                $filename = 'import-agentj-alias-' . time() . ".csv";
                $file = $fileUpload->move('/tmp/', $filename);
                $result = $this->importAlias($file->getPathname());
                //delete file after import
                $fileSystem = new Filesystem();
                $fileSystem->remove([$file->getPathname()]);
                if ($result['user_import'] > 0) {
                    $message = $translator->trans('Generics.flash.ImportSuccess');
                    $message = str_replace('[NB_USER]', strval($result['user_import']), $message);
                    $this->addFlash('success', $message);
                } else {
                    $this->addFlash('warning', 'Generics.flash.NoImport');
                }
            } else {
                $this->addFlash('danger', 'Generics.flash.BadFormatcsv');
            }
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
        }

        return $this->render('import/index_alias.html.twig', [
                    'controller_name' => 'ImportController',
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Import alias from file csv columns : FirstName , LastName, email, email of alias
     *
     * @return array{user_import: integer}
     */
    private function importAlias(string $pathfile): array
    {
        $translator = $this->translator;
        $split = ';';
        $lines = file($pathfile);
        if ($lines === false) {
            return ['user_import' => 0];
        }
        $nbUser = count($lines) - 1;

        $row = 0;
        $import = 0;
        $alreadyExist = [];
        $errors = [];
        $batchSize = 20;

        $em = $this->em;
        $groups = [];
        $emails = [];
        $groupWbUpdate = [];
        $domains = [];
        try {
            if (($handle = fopen($pathfile, "r")) !== false) {
                while (($data = fgetcsv($handle, 1024, $split)) !== false) {
                    //check line number of columns, if empty
                    if ((count($data) != 4)) {
                        $errors[] = $translator->trans('Generics.flash.IncorrectColumns', ['ROW' => $row + 1]);
                        continue;
                    }

                    if (isset($data[3]) && $data[3] != "" && filter_var($data[3], FILTER_VALIDATE_EMAIL)) {
                        $email = $data[3];
                        $domainEmail = strtolower(substr($email, strpos($email, '@') + 1));
                        if (!isset($domains[$domainEmail])) {
                            $domains[$domainEmail]['entity'] = $em->getRepository(Domain::class)->findOneBy([
                                'domain' => $domainEmail,
                            ]);
                        }
                        //need domain exist in database
                        if ($domains[$domainEmail]['entity']) {
                            if (!isset($emails[$email])) {
                                //check if original email's user exist
                                $originalUser = $em->getRepository(User::class)->findOneBy(['email' => $data[2]]);
                                if ($originalUser) {
                                    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                                    //check if user already exist and is not an alias
                                    if ($user and $user->getOriginalUser() == null) {
                                        $errors[] = $translator->trans(
                                            'Generics.flash.ExistingUser',
                                            ['ROW' => $row + 1, 'EMAIL' => $data[3]],
                                        );
                                    } else {
                                        if (!$user) {
                                            $user = new User();
                                        } else {
                                            $alreadyExist[] = $email;
                                        }
                                        $user->setEmail($data[3]);
                                        $user->setUsername($data[3]);
                                        if (isset($data[1]) && isset($data[0])) {
                                            $user->setFullname(trim($data[1] . " " . $data[0]));
                                        }
                                        $import++;
                                        $user->setRoles('["ROLE_USER"]');
                                        $domainEmail = strtolower(substr($data[3], strpos($data[3], '@') + 1));
                                        $domain = $em->getRepository(Domain::class)->findOneBy([
                                            'domain' => $domainEmail,
                                        ]);
                                        $user->setDomain($domain);
                                        $user->setOriginalUser($originalUser);
                                        $user->setPolicy($originalUser->getPolicy());
                                        $em->persist($user);
                                        $emails[$user->getEmail()] = $user->getEmail();
                                    }
                                } else {
                                    $errors[] = $translator->trans(
                                        'Generics.flash.NonexistantUser',
                                        ['ROW' => $row + 1, 'EMAIL' => $data[2]],
                                    );
                                }
                            }
                        } else {
                            $errors[] = $translator->trans(
                                'Generics.flash.NonexistantDomain',
                                ['ROW' => $row + 1, 'DOMAIN' => $domainEmail],
                            );
                        }
                        if ((($row % $batchSize) === 0) || ($row == $nbUser)) {
                            $em->flush();
                        }
                    } else {
                        $errors[] = $translator->trans(
                            'Generics.flash.InvalidEmail',
                            ['ROW' => $row + 1],
                        );
                    }

                    $row++;
                }

                //Need to flush if user new is inferior $batchSize
                $em->flush();
                $this->groupService->updateWblist();
            }
        } catch (\Exception $e) {
            $dangerMessage = $translator->trans(
                'Generics.flash.ImportError',
                ['ERROR_MESSAGE' => $e->getMessage()],
            );
            $this->addFlash('danger', $dangerMessage);
        }

        if (count($errors) > 0) {
            $dangerMessageList = $translator->trans(
                'Generics.flash.ImportErrorsList',
                ['LIST_ROWS_ERROR' => implode('<br> ', $errors)],
            );
            $this->addFlash('danger', $dangerMessageList);
        }

        if (count($alreadyExist) > 0) {
            $warningMessage = $translator->trans(
                'Generics.flash.ImportExisting',
                ['LIST_DUPES' => implode(', ', $alreadyExist)],
            );
            $this->addFlash('warning', $warningMessage);
        }

        return ['user_import' => $import];
    }
}
