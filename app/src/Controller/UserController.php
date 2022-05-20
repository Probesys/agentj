<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Wblist;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\WblistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("admin/users")
 */
class UserController extends AbstractController
{
    use ControllerWBListTrait;

    private $translator;
  /* @var $wblistRepository WblistRepository */
    private $wblistRepository;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->wblistRepository = $em->getRepository(Wblist::class);
    }

  /**
   * @Route("/local", name="users_local_index", methods="GET")
   */
    public function indexUserLocal(UserRepository $userRepository): Response
    {
        $users = $userRepository->searchByRoles($this->getUser(), ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        return $this->render('user/indexLocal.html.twig', ['users' => $users]);
    }

  /**
   * @Route("/email", name="users_email_index", methods="GET")
   */
    public function indexUserEmail(UserRepository $userRepository): Response
    {
        $users = $userRepository->searchByRoles($this->getUser(), ['ROLE_USER']);
        return $this->render('user/indexEmail.html.twig', ['users' => $users]);
    }

  /**
   * @Route("/alias", name="users_email_alias_index", methods="GET")
   */
    public function indexUserEmailAlias(UserRepository $userRepository): Response
    {
        $users = $userRepository->searchAlias($this->getUser());
        return $this->render('user/indexAlias.html.twig', ['users' => $users]);
    }

  /**
   * @Route("/local/new", name="user_local_new", methods="GET|POST")
   */
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
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
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $userNameExist = $this->em->getRepository(User::class)->findOneBy(['username' => $data->getUserName()]);
            if ($userNameExist) {
        //                $this->addFlash('danger', $this->translator->trans('Generics.flash.userNameAllreadyExist'));
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.userNameAllreadyExist'),
                ];
            } elseif ($request->request->get('user')['password']['first'] != $request->request->get('user')['password']['second']) {
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.passwordNotMatching'),
                ];
            } elseif ($form->isValid()) {
                $role = $request->request->get('user')['roles'];
                $encoded = $passwordHasher->hashPassword($user, $request->request->get('user')['password']['first']);
                $user->setPassword($encoded);
                $user->setRoles($role);

                $em = $this->em;
                $em->persist($user);
                $em->flush();
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

  /**
   * @Route("/local/{id}/edit", name="user_local_edit", methods="GET|POST")
   *
   */
    public function edit(Request $request, User $user, EntityManagerInterface $em): Response
    {
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userNameExist = $this->em->getRepository(User::class)->findOneBy(['username' => $request->request->get('user')['username']]);
            $oldUser = $em->getUnitOfWork()->getOriginalEntityData($user);
            if ($oldUser['username'] != $request->request->get('user')['username'] && $userNameExist) {
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.userNameAllreadyExist'),
                ];
            } else {
                $role = $request->request->get('user')['roles'];
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

  /**
   * @Route("/local/{id}/changePassword", name="user_local_change_password", methods="GET|POST")
   *
   */
    public function changePassword(Request $request, User $user, UserPasswordHasherInterface $passwordHasher): Response
    {
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encoded = $passwordHasher->hashPassword($user, $request->request->get('user')['password']['first']);
            $user->setPassword($encoded);
            $this->em->flush();

            return $this->redirectToRoute('users_local_index', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
                'user' => $user,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/local/{id}/delete", name="user_local_delete", methods="GET")
   */
    public function deleteLocal(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->query->get('_token'))) {
            $em = $this->em;
            $em->remove($user);
            $em->flush();
        }
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

  /**
   * @Route("/email/{id}/delete", name="user_email_delete", methods="GET")
   */
    public function deleteEmail(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->query->get('_token'))) {
            if (in_array('ROLE_USER', $user->getRoles())) {
                $em = $this->em;
                $em->remove($user);
                $em->flush();
            }
        }
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

  /**
   * @Route("/email/batchDelete", name="user_email_batch_delete", methods="POST")
   */
    public function batchDeleteEmail(Request $request): Response
    {
        $em = $this->em;
        foreach ($request->request->get('id') as $id) {
            $user = $em->getRepository(User::class)->find($id);
            if ($user) {
                $em->remove($user);
            }
        }
        $em->flush();
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

  /**
   * @Route("/email/newUser", name="user_email_new", methods="GET|POST")
   */
    public function newUserEmail(Request $request, UserRepository $userRepository): Response
    {
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
            $newDomain = $this->checkDomainAccess(explode('@', $request->request->get('user')['email'])[1]);
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
            } elseif ($form->isValid()) {
                $user->setRoles("['ROLE_USER']");
                $user->setUsername($user->getEmail());
                $user->setDomain($newDomain);
                $policy = $this->computeUserPolicy($user);
                $user->setPolicy($policy);

                $em = $this->em;

                $em->persist($user);
                $em->flush();

                if ($user->getGroups()) {
                    $this->updatedWBListFromGroup($user->getGroups()->getId());
                }


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

  /**
   * @Route("/newAlias", name="new_user_email_alias", methods="GET|POST")
   */
    public function newUserAlias(Request $request): Response
    {
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
            $newDomain = $this->checkDomainAccess(explode('@', $request->request->get('user')['email'])[1]);
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
                $user->setRoles("['ROLE_USER']");
                $user->setDomain($newDomain);
                $user->setUsername($user->getEmail());
                if ($user->getOriginalUser() && $groups = $user->getOriginalUser()->getGroups()) {
                    $user->setGroups($groups);
                }
                $user->setUsername($user->getEmail());

                $policy = $this->computeUserPolicy($user);
                $user->setPolicy($policy);
                $em = $this->em;
                $em->persist($user);
                $em->flush();

                $em->getRepository(Wblist::class)->importWbListFromUserToAlias($user);

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

  /**
   * @Route("/email/{id}/edit", name="user_email_edit", methods="GET|POST")
   *
   */
    public function editUserEmail(Request $request, User $user, EntityManagerInterface $em): Response
    {

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
            $oldUserData = $em->getUnitOfWork()->getOriginalEntityData($user);

            $newDomainName = explode('@', $request->request->get('user')['email'])[1];
            if (!$this->checkDomainAccess($newDomainName)) {
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.domainNotExist'),
                ];
            } elseif (stream_get_contents($oldUserData['email'], -1, 0) != $request->request->get('user')['email'] && $emailExist) {
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.emailAllreadyExist'),
                ];
            } elseif ($form->isValid()) {
                $this->em->getRepository(User::class)->updateAliasGroupsFromUser($user);
                $policy = $this->computeUserPolicy($user);
                $user->setPolicy($policy);
                $user->setUsername($user->getEmail());
                $this->em->flush();



              //Alias user and alias group management

                $this->wblistRepository->deleteUserGroup($user->getId());
                $alias = $em->getRepository(User::class)->findBy(['originalUser' => $user->getId()]);
                foreach ($alias as $userAlias) {
                    $this->wblistRepository->deleteUserGroup($userAlias->getId());
                    $userAlias->setGroups($user->getGroups());
                }

              // Add wblist rules from group if user is in a group
                if ($user->getGroups()) {
                    $this->updatedWBListFromGroup($user->getGroups()->getId());
                }

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

  /**
   * @Route("/alias/{id}/edit", name="user_email_alias_edit", methods="GET|POST")
   *
   */
    public function editUserEmailAlias(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $userId = null;
        if ($user->getOriginalUser()) {
            $userId = $user->getOriginalUser()->getId();
        }

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
            $oldUser = $em->getUnitOfWork()->getOriginalEntityData($user);
            if (stream_get_contents($oldUser['email'], -1, 0) != $request->request->get('user')['email'] && $emailExist) {
                $return = [
                'status' => 'danger',
                'message' => $this->translator->trans('Generics.flash.emailAllreadyExist'),
                ];
            } elseif ($form->isValid()) {
                $user->setUsername($user->getEmail());
                $user->setGroups($user->getOriginalUser()->getGroups());
                $this->em->flush();

                $this->wblistRepository->deleteUserGroup($user->getId());
                if ($user->getGroups()) {
                    $this->updatedWBListFromGroup($user->getGroups()->getId());
                }


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

  private function computeUserPolicy(User $suser)
    {
        if ($suser->getGroups() && $suser->getGroups()->getActive()) {
            $policy = $suser->getGroups()->getPolicy();
        } else {
            $policy = $suser->getDomain()->getPolicy();
        }
        return $policy;
    }

    private function getAlloweDomains()
    {
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

    private function checkDomainAccess(string $domainName = "")
    {
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
   *
   * @Route("/user/autocomplete", name="autocomplete_user", options={"expose"=true})
   */
    public function autocompleteUserAction(UserRepository $userRepository, Request $request)
    {

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

            $items[] = ['id' => $entity['id'], 'text' => $label, 'label' => $label];
        }

        if ($request->query->get('field_name')) { // from Select2EntityType
            $return['results'] = $items;

            $return['more'] = true;
        } else {
            $return['length'] = count($entities);

            $return['items'] = $items;
        }

        return new Response(json_encode($return), $return ? 200 : 404);
    }

  /**
   * autocomplete user action. search in user in specified domains
   *
   * @Route("/user/loadUserDomain", name="load_user_domain", options={"expose"=true})
   */
    public function loadUserDomain(UserRepository $userRepository, Request $request)
    {
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
