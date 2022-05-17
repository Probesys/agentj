<?php

namespace App\Controller;

use App\Controller\Traits\ControllerWBListTrait;
use App\Entity\Groups;
use App\Entity\GroupsWblist;
use App\Entity\Mailaddr;
use App\Form\GroupsWblistType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 * @Route("/groups/wblist")
 */
class GroupsWblistController extends AbstractController
{
    use ControllerWBListTrait;

    private $translator;

    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
        
    private function checkAccess($group)
    {
        if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
            if (!$group->getDomain()->getUsers()->contains($this->getUser())) {
                throw new AccessDeniedException();
            }
        }
    }

  /**
   * @Route("/{groupId}", name="groups_wblist_index", methods="GET")
   */
    public function index($groupId): Response
    {
        $groupswblists = $this->em->getRepository(GroupsWblist::class)->findBy((['groups' => $groupId]));

        $groups = $this->em->getRepository(Groups::class)->find($groupId);
        $this->checkAccess($groups);

        return $this->render('groups_wblist/index.html.twig', ['groups_wblists' => $groupswblists, 'groups' => $groups]);
    }

  /**
   * @Route("/{groupId}/new", name="groups_wblist_new", methods="GET|POST")
   */
    public function new($groupId, Request $request): Response
    {
        $groups = $this->em->getRepository(Groups::class)->findOneBy((['id' => $groupId]));
        if (!$groups) {
            throw $this->createNotFoundException('The groups does not exist');
        }

        $groupsWblist = new GroupsWblist();


        $form = $this->createForm(GroupsWblistType::class, null, [
        'actions' => $this->wBListUserActions,
        'action' => $this->generateUrl('groups_wblist_new', ['groupId' => $groups->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $data['email']]));
            if (!$mailaddr) {
                //vérifier que c'est utile car normalement l'email doit déjà être dans la base
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail($data['email']);
                $em->persist($mailaddr);
            } else {
              /** @vars Wblist $groupsWblistexist */
                $groupsWblistexist = $this->em->getRepository(GroupsWblist::class)->findOneBy((['mailaddr' => $mailaddr, 'groups' => $groups]));
                if ($groupsWblistexist) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('groups_wblist_index', ['groupId' => $groupId]);
                }
            }

            $groupsWblist->setMailaddr($mailaddr);

            $groupsWblist->setGroups($groups);
            $groupsWblist->setWb($data['wb']);

            $em->persist($groupsWblist);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.newRuleCreated'));

            $this->updatedWBListFromGroup($groupId);

            return $this->redirectToRoute('groups_wblist_index', ['groupId' => $groupId]);
        }

        return $this->render('groups_wblist/new.html.twig', [
                'groups_wblist' => $groupsWblist,
                'group' => $groups,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/{groupId}/edit/{sid}", name="groups_wblist_edit", methods="GET|POST")
   */
    public function edit($groupId, $sid, Request $request)
    {
        $groupWbList = $this->em->getRepository(GroupsWblist::class)->findOneBy(['mailaddr' => $sid, 'groups' => $groupId]);
        $group = $this->em->getRepository(Groups ::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);
        $form = $this->createForm(GroupsWblistType::class, null, [
        'actions' => $this->wBListUserActions,
        'action' => $this->generateUrl('groups_wblist_edit', ['groupId' => $group->getId(), 'sid' => $sid]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;

            $data = $form->getData();

            $mailaddr = $this->em->getRepository(Mailaddr::class)->findOneBy((['email' => $data['email']]));
            if (!$mailaddr) {
                //vérifier que c'est utile car normalement l'email doit déjà être dans la base
                $mailaddr = new Mailaddr();
                $mailaddr->setEmail($data['email']);
                $em->persist($mailaddr);
            } else {
                $groupsWblistexist = $this->em->getRepository(GroupsWblist::class)->findOneBy((['mailaddr' => $mailaddr, 'groups' => $group]));
                if ($groupsWblistexist && $mailaddr != $groupWbList->getMailaddr()) {
                    $this->addFlash('warning', $this->translator->trans('Message.Flash.ruleExists'));
                    return $this->redirectToRoute('groups_wblist_new', ['groupId' => $groupId]);
                }
            }

            $groupWbList->setMailaddr($mailaddr);
            $groupWbList->setGroups($group);
            $groupWbList->setWb($data['wb']);

            $em->persist($groupWbList);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Message.Flash.ruleUpdated'));

            $this->updatedWBListFromGroup($groupId);

            return $this->redirectToRoute('groups_wblist_index', ['groupId' => $groupId]);
        } else {
            $form->get('email')->setData($groupWbList->getMailaddr()->getEmail());
            $form->get('wb')->setData($groupWbList->getWb());
        }

        return $this->render('groups_wblist/edit.html.twig', [
                'groups_wblist' => $groupWbList,
                'group' => $group,
                'form' => $form->createView(),
        ]);
    }

  /**
   * @Route("/{groupId}/{sid}/delete", name="groups_wblist_delete", methods="GET")
   */
    public function delete($groupId, $sid, Request $request)
    {
        $group = $this->em->getRepository(Groups ::class)->findOneBy(['id' => $groupId]);
        $this->checkAccess($group);
        $groupsWblist = $this->em->getRepository(GroupsWblist::class)->findOneBy((['mailaddr' => $sid, 'groups' => $groupId]));
        if ($groupsWblist) {
            $em = $this->em;
            $em->remove($groupsWblist);
            $em->flush();
            $this->updatedWBListFromGroup($groupId);
        }

        return $this->redirectToRoute('groups_wblist_index', ['groupId' => $groupId]);
    }
}
