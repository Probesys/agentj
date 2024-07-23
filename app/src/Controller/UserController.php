<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Policy;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\GroupService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
#[Route(path: 'admin/users')]
class UserController extends AbstractController {

    use ControllerWBListTrait;

    private TranslatorInterface $translator;
    private EntityManagerInterface $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em) {
        $this->translator = $translator;
        $this->em = $em;
    }

    #[Route(path: '/local', name: 'users_local_index', methods: 'GET')]
    public function indexUserLocal(UserRepository $userRepository): Response {
        $users = $userRepository->searchByRole($this->getUser(), '["ROLE_ADMIN"]');
        return $this->render('user/indexLocal.html.twig', ['users' => $users]);
    }

    #[Route(path: '/email', name: 'users_email_index', methods: 'GET')]
    public function indexUserEmail(UserRepository $userRepository): Response {
        $users = $userRepository->searchByRole($this->getUser(), '["ROLE_USER"]');
        return $this->render('user/indexEmail.html.twig', ['users' => $users]);
    }

    #[Route(path: '/alias', name: 'users_email_alias_index', methods: 'GET')]
    public function indexUserEmailAlias(UserRepository $userRepository): Response {
        $users = $userRepository->searchAlias($this->getUser());
        return $this->render('user/indexAlias.html.twig', ['users' => $users]);
    }

    #[Route(path: '/local/new', name: 'user_local_new', methods: 'GET|POST')]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_local_new'),
            'attr' => ['class' => 'modal-ajax-form'],
        ]);
        $form->remove('groups');
        $form->remove('email');
        $form->remove('originalUser');
        $form->remove('report');
        $form->remove('sharedWith');
        $form->remove('imapLogin');
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $userNameExist = $this->em->getRepository(User::class)->findOneBy(['username' => $data->getUserName()]);
//            dd($form->get('password')->get('first')->getData());
            if ($userNameExist) {
                //                $this->addFlash('danger', $this->translator->trans('Generics.flash.userNameAllreadyExist'));
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.userNameAllreadyExist'),
                ];
            } elseif ($form->get('password')->get('first')->getData() != $form->get('password')->get('second')->getData()) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.passwordNotMatching'),
                ];
            } elseif ($form->isValid()) {
                $role = $form->get('roles')->getData();
                $encoded = $passwordHasher->hashPassword($user, $form->get('password')->get('first')->getData());
                $policy = $this->em->getRepository(Policy::class)->find(5);
                $user->setPassword($encoded);
                $user->setPolicy($policy);
                $user->setUsername($user->getFullname());
                $user->setRoles($role);

                $this->em->persist($user);
                $this->em->flush();
                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.addSuccess'),
                ];
            }


            return new Response(json_encode($return), 200);
        }

        return $this->render('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    
    #[Route(path: '/local/{id}/edit', name: 'user_local_edit', methods: 'GET|POST')]
    public function edit(Request $request, User $user): Response {
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_local_edit', ['id' => $user->getId()]),
            'attr' => ['class' => 'modal-ajax-form']
        ]);
        $form->remove('groups');
        $form->remove('email');
        $form->remove('password');
        $form->remove('originalUser');
        $form->remove('report');
        $form->remove('sharedWith');
        $form->remove('imapLogin');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userNameExist = $this->em->getRepository(User::class)->findOneBy(['username' => $form->get('username')->getData()]);
            $oldUser = $this->em->getUnitOfWork()->getOriginalEntityData($user);
            if ($oldUser['username'] != $form->get('username')->getData() && $userNameExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.userNameAllreadyExist'),
                ];
            } else {
                $role = $form->get('roles')->getData();
                $user->setRoles($role);

                $this->em->flush();
                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.addSuccess'),
                ];
            }

            return new Response(json_encode($return), 200);
        }

        return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    
    #[Route(path: '/local/{id}/changePassword', name: 'user_local_change_password', methods: 'GET|POST')]
    public function changePassword(Request $request, User $user, UserPasswordHasherInterface $passwordHasher): Response {
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_local_change_password', ['id' => $user->getId()]),
        ]);
        $form->remove('fullname');
        $form->remove('username');
        $form->remove('roles');
        $form->remove('emailRecovery');
        $form->remove('domains');
        $form->remove('groups');
        $form->remove('email');
        $form->remove('originalUser');
        $form->remove('report');
        $form->remove('sharedWith');
        $form->remove('imapLogin');
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if (!$form->isValid()) {
                $errros = $form->getErrors(true);
//                dd($errros[0]->getMessage());
                $this->addFlash('error', $errros[0]->getMessage());
            } else {
                $encoded = $passwordHasher->hashPassword($user, $form->get('password')->get('first')->getData());
                $user->setPassword($encoded);
                $this->em->flush();
            }


            return $this->redirectToRoute('users_local_index', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/local/{id}/delete', name: 'user_local_delete', methods: 'GET')]
    public function deleteLocal(Request $request, User $user): Response {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->query->get('_token'))) {
            $this->em->remove($user);
            $this->em->flush();
        }
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route(path: '/email/{id}/delete', name: 'user_email_delete', methods: 'GET')]
    public function deleteEmail(Request $request, User $user): Response {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->query->get('_token'))) {
            if (in_array('ROLE_USER', $user->getRoles())) {
                $this->em->remove($user);
                $this->em->flush();
            }
        }
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route(path: '/email/batchDelete', name: 'user_email_batch_delete', methods: 'POST')]
    public function batchDeleteEmail(Request $request): Response {

        foreach ($request->request->all('id') as $id) {
            $user = $this->em->getRepository(User::class)->find($id);
            if ($user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route(path: '/email/newUser', name: 'user_email_new', methods: 'GET|POST')]
    public function newUserEmail(Request $request, UserRepository $userRepository, UserService $userService, GroupService $groupService): Response {
        $user = new User();

        $allowedomainIds = array_map(function ($entity) {

            if ($entity) {
                return $entity->getId();
            }
        }, $this->getAlloweDomains());

        $form = $this->createForm(
                UserType::class,
                $user,
                [
                    'action' => $this->generateUrl('user_email_new'),
                    'allowedomainIds' => $allowedomainIds,
                    'attr' => ['class' => 'modal-ajax-form']
                ]
        );
        $form->remove('password');
        $form->remove('domains');
        $form->remove('roles');
        $form->remove('username');
        $form->remove('originalUser');

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();

            $emailExist = $userRepository->findOneBy(['email' => $data->getEmail()]);
//            dd($form->get('email')->getData());
            $newDomain = $this->checkDomainAccess(explode('@', $form->get('email')->getData())[1]);
            $imapLoginExist = !$data->getImapLogin() ? false : $userRepository->findOneBy(['imapLogin' => $data->getImapLogin(), 'domain' => $newDomain]);

            if (!$newDomain) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.domainNotExist'),
                ];
            } elseif ($emailExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.emailAllreadyExist'),
                ];
            } elseif ($imapLoginExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.imapLoginAllreadyExist'),
                ];
            } elseif ($form->isValid()) {
                $user->setRoles('["ROLE_USER"]');
                $user->setUsername($user->getEmail());
                $user->setDomain($newDomain);

                $policy = $this->computeUserPolicy($user);
                $user->setPolicy($policy);

                $this->em->persist($user);
                $this->em->flush();

                $userService->updateAliasGroupsAndPolicyFromUser($user);
                $groupService->updateWblist();

                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.addSuccess'),
                ];

                return new Response(json_encode($return), 200);
            } else {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.genericFormError'),
                ];
            }
            return new Response(json_encode($return), 200);
        }

        return $this->render('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/newAlias', name: 'new_user_email_alias', methods: 'GET|POST')]
    public function newUserAlias(Request $request, UserService $userService, GroupService $groupService): Response {
        $groups = null;
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'alias' => true,
            'action' => $this->generateUrl('new_user_email_alias'),
            'attr' => ['class' => 'modal-ajax-form']
        ]);
        $form->remove('fullname');
        $form->remove('groups');
        $form->remove('password');
        $form->remove('domains');
        $form->remove('roles');
        $form->remove('username');
        $form->remove('emailRecovery');
        $form->remove('sharedWith');
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $aliaslExist = $this->em->getRepository(User::class)->findOneBy(['email' => $data->getEmail()]);
            $newDomain = $this->checkDomainAccess(explode('@', $form->get('email')->getData())[1]);
            if (!$newDomain) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.domainNotExist'),
                ];
            } elseif ($aliaslExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.aliasAllreadyExist'),
                ];
            } elseif ($form->isValid()) {
                $user->setRoles('["ROLE_USER"]');
                $user->setDomain($newDomain);
                $user->setUsername($user->getEmail());

                $user->setPolicy($user->getOriginalUser()->getPolicy());

                $this->em->persist($user);
                $this->em->flush();

                $userService->updateAliasGroupsAndPolicyFromUser($user->getOriginalUser());
                $groupService->updateWblist();
                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.addSuccess'),
                ];
            } else {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.genericFormError'),
                ];
            }
            return new Response(json_encode($return), 200);
        }

        return $this->render('user/newAlias.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    
    #[Route(path: '/email/{id}/edit', name: 'user_email_edit', methods: 'GET|POST')]
    public function editUserEmail(User $user, Request $request, UserService $userService, GroupService $groupService, UserRepository $userRepository): Response {

        $oldGroups = $user->getGroups()->toArray();

        $allowedomainIds = array_map(function ($entity) {

            if ($entity) {
                return $entity->getId();
            }
        }, $this->getAlloweDomains());

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_email_edit', ['id' => $user->getId()]),
            'allowedomainIds' => $allowedomainIds,
            'attr' => ['class' => 'modal-ajax-form']
        ]);
        $form->remove('password');
        $form->remove('domains');
        $form->remove('roles');
        $form->remove('username');
        $form->remove('originalUser');
        $form->get('email')->setData(stream_get_contents($user->getEmail(), -1, 0));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            //Check, if user email was changed, if the new mail does not allready exist
            $emailExist = $this->em->getRepository(User::class)->findOneBy(['email' => $data->getEmail()]);
            $oldUserData = $this->em->getUnitOfWork()->getOriginalEntityData($user);

//            $newDomainName = explode('@', $form->get('email')->getData())[1];
            $newDomain = $this->checkDomainAccess(explode('@', $form->get('email')->getData())[1]);

            $imapLoginExist = !$data->getImapLogin() ? false : $userRepository->findOneBy(['imapLogin' => $data->getImapLogin(), 'domain' => $newDomain]);
            if (!$newDomain) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.domainNotExist'),
                ];
            } elseif (stream_get_contents($oldUserData['email'], -1, 0) != $form->get('email')->getData() && $emailExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.emailAllreadyExist'),
                ];
            } elseif ($oldUserData['imapLogin'] != $form->get('imapLogin')->getData() && $imapLoginExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.imapLoginAllreadyExist'),
                ];
            } elseif ($form->isValid()) {

                $policy = $this->computeUserPolicy($user);
                $user->setPolicy($policy);
                $user->setUsername($user->getEmail());

                $this->em->persist($user);
                $this->em->flush();

                $userService->updateUserAndAliasPolicy($user);
                $userService->updateAliasGroupsAndPolicyFromUser($user);

                $groupService->updateWblist();

                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.editSuccess'),
                ];
            }

            return new Response(json_encode($return), 200);
        }

        return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    
    #[Route(path: '/alias/{id}/edit', name: 'user_email_alias_edit', methods: 'GET|POST')]
    public function editUserEmailAlias(Request $request, User $user, UserService $userService, GroupService $groupService): Response {
        $userId = null;
        if ($user->getOriginalUser()) {
            $userId = $user->getOriginalUser()->getId();
        }

        $oldAliasGroups = $user->getGroups()->toArray();
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('user_email_alias_edit', ['id' => $user->getId()]),
            'attr' => ['class' => 'modal-ajax-form']
        ]);

        $form->remove('fullname');
        $form->remove('groups');
        $form->remove('password');
        $form->remove('domains');
        $form->remove('roles');
        $form->remove('username');
        $form->remove('emailRecovery');
        $form->remove('sharedWith');
        $form->get('email')->setData(stream_get_contents($user->getEmail(), -1, 0));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData(); //dump();die;
            $emailExist = $this->em->getRepository(User::class)->findOneBy(['email' => $data->getEmail()]);
            $oldUser = $this->em->getUnitOfWork()->getOriginalEntityData($user);
            if (stream_get_contents($oldUser['email'], -1, 0) != $form->get('email')->getData() && $emailExist) {
                $return = [
                    'status' => 'danger',
                    'message' => $this->translator->trans('Generics.flash.emailAllreadyExist'),
                ];
            } elseif ($form->isValid()) {
                $user->setUsername($user->getEmail());
                $this->em->flush();

                $userService->updateAliasGroupsAndPolicyFromUser($user->getOriginalUser());
                $groupService->updateWblist();

                $return = [
                    'status' => 'success',
                    'message' => $this->translator->trans('Generics.flash.editSuccess'),
                ];
            }

            return new Response(json_encode($return), 200);
        }
        //        $form->get('email')->setData(stream_get_contents($user->getEmail(), -1, 0));

        return $this->render('user/edit.html.twig', [
                    'user' => $user,
                    'form' => $form->createView(),
        ]);
    }

    /**
     * Return the plociy of user if exist. Else return the domain policy
     * @param User $user
     * @return Policy
     */
    private function computeUserPolicy(User $user): Policy {
        $policy = null;
        $groupsRepository = $this->em->getRepository(Groups::class);
        if ($user->getGroups() && count($user->getGroups()) > 0) {
            $defaultGroup = array_reduce($user->getGroups()->toArray(), function ($a, $b) {
                return $a && $a->getPriority() > $b->getPriority() ? $a : $b;
            });

            $policy = $defaultGroup ? $defaultGroup->getPolicy() : null;
        }

        if (!$policy) {
            $policy = $user->getDomain()->getPolicy();
        }
        return $policy;
    }

    private function getAlloweDomains() {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $allowedomains = $this->em
                    ->getRepository(Domain::class)
                    ->getListByUserId($this->getUser()->getId());
        } else {
            $allowedomains = $this->em
                    ->getRepository(Domain::class)
                    ->findAll();
        }
        return $allowedomains;
    }

    private function checkDomainAccess(string $domainName = "") {
        $allowedomains = $this->getAlloweDomains();
        $allowedomainNamesArray = array_map(function ($entity) {
            if ($entity) {
                return $entity->getDomain();
            }
        }, $allowedomains);
        if (!in_array($domainName, $allowedomainNamesArray)) {
            return false;
        } else {
            return $this->em
                            ->getRepository(Domain::class)
                            ->findOneBy(['domain' => $domainName]);
        }
    }

    /**
     * autocomplete user action. search in user
     */
    #[Route(path: '/user/autocomplete', name: 'autocomplete_user', options: ['expose' => true])]
    public function autocompleteUserAction(UserRepository $userRepository, Request $request) {

        $q = $request->query->get('q');

        $return = [];

        $items = [];
        $allowedomains = [];
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            $allowedomains = $this->em
                    ->getRepository(Domain::class)
                    ->getListByUserId($this->getUser()->getId());
        }

        $entities = $userRepository->autocomplete($q, $request->query->get('page_limit'), $request->query->get('page'), $allowedomains);

        foreach ($entities as $entity) {
            $label = '' . stream_get_contents($entity['email'], -1, 0);

            $items[] = ['value' => $entity['id'], 'text' => $label];
        }
        $return['results'] = $items;
        return new Response(json_encode($return), $return ? 200 : 404);
    }

    /**
     * autocomplete user action. search in user in specified domains
     */
    #[Route(path: '/user/loadUserDomain', name: 'load_user_domain', options: ['expose' => true])]
    public function loadUserDomain(UserRepository $userRepository, Request $request) {
        $q = $request->request->get('q');
        $domain = $request->request->get('domain');
        $return = [];

        $items = [];

        $entities = $userRepository->searchInDomains($domain, $q, $request->query->get('page_limit'), $request->query->get('page'));
        foreach ((array) $entities as $entity) {
            $label = '' . stream_get_contents($entity['email'], -1, 0);

            $items[] = ['id' => $entity['id'], 'text' => $label, 'label' => $label];
        }

        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;
            $return['more'] = true;
        } elseif ($entities) {
            $return['length'] = count($entities);
            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }
}
