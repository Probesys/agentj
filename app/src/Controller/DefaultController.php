<?php

namespace App\Controller;

use App\Controller\MessageController;
use App\Entity\Domain;
use App\Entity\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Form\CaptchaFormType;
use App\Service\CryptEncrypt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultController extends AbstractController {

  private $params;
  private $translator;

  public function __construct(ParameterBagInterface $params, TranslatorInterface $translator) {
    $this->params = $params;
    $this->translator = $translator;
  }

  /**
   * @Route("/", name="homepage")
   */
  public function index() {
    $alias = $this->getDoctrine()->getManager()->getRepository(User::class)->findBy(['originalUser' => $this->getUser()->getId()]);
    $nbUntreadtedMsgByDay = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::UNTREATED, $alias);
    $nbAutorizeMsgByDay = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::AUTHORIZED, $alias);
    $nbBannedMsgByDay = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::BANNED, $alias);
    $nbDeletedMsgByDay = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::DELETED, $alias);
    $nbRestoredMsgByDay = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->countByTypeAndDays($this->getUser(), MessageStatus::RESTORED, $alias);


    $labels = array_map(function($item) {
      return $item['time_iso'];
    }, $nbUntreadtedMsgByDay);



    return $this->render('home/index.html.twig', [
                'controller_name' => 'DefaultController',
                'listDay' => $labels,
                'nbUntreadtedMsgByDay' => $nbUntreadtedMsgByDay,
                'nbAutorizeMsgByDay' => $nbAutorizeMsgByDay,
                'nbBannedMsgByDay' => $nbBannedMsgByDay,
                'nbDeletedMsgByDay' => $nbDeletedMsgByDay,
                'nbRestoredMsgByDay' => $nbRestoredMsgByDay,
    ]);
  }

  /**
   * @Route("/check/{token}", name="check_message", methods="GET|POST")
   */
  public function checkCaptcha($token, Request $request, MessageController $messageController, CryptEncrypt $cryptEncrypt) {
    $em = $this->getDoctrine()->getManager();
    $confirm = "";

    $decrypt = $cryptEncrypt->decryptUrl($token);
    $mailId = null;
    $date = null;
    $rid = null;
    if (isset($decrypt[0]) && isset($decrypt[1])) {
      $date = $decrypt[0];
      $msg = explode('%%%', $decrypt[1]);
      if (isset($msg[0]) && isset($msg[1])) {
        $mailId = $msg[0];
        if (isset($msg[0]) && isset($msg[4])) {
          $rid = $msg[4];
        }
        $partitionTag = $msg[2];
      } else {
        throw $this->createNotFoundException('This page does not exist');
      }
    }

    if (!$mailId) {
      throw $this->createNotFoundException('This page does not exist');
    }

    $msgs = $this->getDoctrine()->getManager()->getRepository(Msgs::class)->findOneBy(['mailId' => $mailId]);

    if (!$msgs) {
      throw $this->createNotFoundException('This page does not exist');
    }

    $content = "";
    $domainId = $msg[3]; //need domain Id to get message
    $domain = $em->getRepository(Domain::class)->find($domainId);

    /* @var $msgObj Msgs */
    $msgObj = $em->getRepository(Msgs::Class)->findOneBy(['mailId' => $mailId]);
    $senderEmail = stream_get_contents($msgObj->getSid()->getEmail(), -1, 0);
    if ($domain) {
      $content = !empty($domain->getMessage()) ? $domain->getMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaPageContent');
    }

    $form = $this->createForm(CaptchaFormType::class, null);
    $form->handleRequest($request); //todo gérer l'erreur si la page est rechargée
    if ($form->isSubmitted() && $form->isValid()) {

      if ($form->has('email') && $form->get('email')->getData() == $senderEmail) { // Test is the sender is the same than the posted email field
        if ($form->has('emailEmpty') && empty($form->get('emailEmpty')->getData())) { // Test HoneyPot
          $messageController->msgsToWblist($partitionTag, $mailId, "W", MessageStatus::AUTHORIZED, 1, $rid); // 2 : authorized and 1 : captcha validate
          $msgs->setValidateCaptcha(time());
          $em->persist($msgs);
          $em->flush();
          if ($domain) {
            $confirm = !empty($domain->getConfirmCaptchaMessage()) ? $domain->getConfirmCaptchaMessage() : $this->translator->trans('Message.Captcha.defaultCaptchaConfirmMsg');
            $mailId = stream_get_contents($msgs->getMailId(), -1, 0);
            $recipient = $em->getRepository(Msgrcpt::class)->findOneBy(['mailId' => $mailId]);
            if ($recipient) {
              $confirm = str_replace('[EMAIL_DEST]', stream_get_contents($recipient->getRid()->getEmail(), -1, 0), $confirm);
            }
          }
        } else {
          $this->addFlash('error', $this->translator->trans('Message.Flash.checkMailUnsuccessful'));
        }
      } else {
        $this->addFlash('error', $this->translator->trans('Message.Flash.checkMailUnsuccessful'));
      }


      //todo authorized ou benned le reste rid, sid dans msgs
    } else {
      $form->get('email')->setData($senderEmail);
    }

//    $this->addFlash('error', $this->translator->trans('Message.Flash.deleteSuccesFull'));
    return $this->render('message/captcha.html.twig', [
                'token' => $token,
                'mailToValidate' => $senderEmail,
                'form' => $form->createView(),
                'confirm' => $confirm,
                'content' => $content
    ]);
  }

  /**
   * Change the locale for the current user.
   *
   * @Route("/setlocale/{language}", name="setlocale")
   *
   * @param Request $request
   * @param string  $language
   *
   * @return Response
   */
  public function setLocaleAction(Request $request, $language = null) {
    if (null != $language) {
      $this->get('session')->set('_locale', $language);
    }

    $url = $request->headers->get('referer');
    if (empty($url)) {
      $url = $this->container->get('router')->generate('index');
    }

    return new RedirectResponse($url);
  }

}