<?php

namespace App\Controller;

use App\Entity\User;
use App\Controller\Traits\ControllerWBListTrait;
use App\Form\UserPreferencesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    use ControllerWBListTrait;

  /**
   * @Route("/account", name="account")
   */
    public function index(Request $request)
    {

      /**@var  User  $user */
        $user = $this->getUser();
        $form = $this->createForm(UserPreferencesType::class, $this->getUser(), [
        'action' => $this->generateUrl('account'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($this->getUser());
            $this->getDoctrine()->getManager()->flush();
        }

        $wbDomain = $this->getDoctrine()->getRepository(\App\Entity\Wblist::class)->getDefaultDomainWBList($user->getDomain());
        $domainDefaulWb = array_keys(array_filter($this->wBListDomainActions, function ($item) use ($wbDomain) {
            return $item == $wbDomain;
        }))[0];

        if ($user->getGroups() && $user->getGroups()->getWb()) {
            $wbGroup = $user->getGroups()->getWb();
            $groupDefaulWb = array_keys(array_filter($this->wBListUserActions, function ($item) use ($wbGroup) {
                return $item == $wbGroup;
            }))[0];
        } else {
            $groupDefaulWb = null;
        }


        return $this->render('account/index.html.twig', [
                'controller_name' => 'AccountController',
                'domainDefaulWb' => $domainDefaulWb,
                'groupDefaulWb' => $groupDefaulWb,
                'form' => $form->createView(),
        ]);
    }
}
