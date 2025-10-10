<?php

namespace App\Controller;

use App\Entity\Wblist;
use App\Form\CaptchaFormType;
use App\Repository\MsgsRepository;
use App\Repository\WblistRepository;
use App\Service\HumanAuthenticationService;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class HumanAuthenticationsController extends AbstractController
{
    #[Route(path: '/check/{token}', name: 'human_authentication', methods: ['GET', 'POST'])]
    public function show(
        string $token,
        Request $request,
        MsgsRepository $messageRepository,
        WblistRepository $wblistRepository,
        MessageService $messageService,
        HumanAuthenticationService $humanAuthenticationService,
        TranslatorInterface $translator,
    ): Response {
        list($message, $domain, $messageRecipients) = $humanAuthenticationService->decryptToken($token);

        if (!$message) {
            throw $this->createNotFoundException('The message does not exist.');
        }

        if (!$domain) {
            throw $this->createNotFoundException('The domain does not exist.');
        }

        $sender = $message->getSid();
        $senderEmail = $sender->getEmailClear();

        // Keep only recipients that are NOT already in a wblist. This avoids,
        // for a instance, a blocked sender to authorise himself.
        $messageRecipients = array_filter(
            $messageRecipients,
            function ($recipient) use ($wblistRepository, $sender) {
                return !$wblistRepository->isRecipientInSenderList($sender, $recipient->getRid());
            }
        );

        if (!empty($domain->getMessage())) {
            $content = $domain->getMessage();
        } else {
            $content = $translator->trans('Message.Captcha.defaultCaptchaPageContent');
        }

        $form = $this->createForm(CaptchaFormType::class, null);
        $form->handleRequest($request);

        $confirm = '';

        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($domain->getConfirmCaptchaMessage())) {
                $confirm = $domain->getConfirmCaptchaMessage();
            } else {
                $confirm = $translator->trans('Message.Captcha.defaultCaptchaConfirmMsg');
            }

            $recipient = $messageRecipients[0] ?? null;
            if ($recipient) {
                $emailDest = $recipient->getRid()->getEmailClear();
                $emailDest = $emailDest ? $emailDest : '';
                $confirm = str_replace('[EMAIL_DEST]', $emailDest, $confirm);
            }

            // Test if the sender is the same than the posted email field and if the mail has not been yet treated
            if (!$message->getStatus() && $form->has('email') && $form->get('email')->getData() == $senderEmail) {
                if ($form->has('emailEmpty') && empty($form->get('emailEmpty')->getData())) { // Test HoneyPot
                    $messageService->authorize($message, $messageRecipients, Wblist::WBLIST_TYPE_AUTHENTICATION);
                    $message->setValidateCaptcha(time());
                    $messageRepository->save($message);
                } else {
                    $this->addFlash('error', $translator->trans('Message.Flash.checkMailUnsuccessful'));
                }
            } else {
                $this->addFlash('error', $translator->trans('Message.Flash.checkMailUnsuccessful'));
            }
        } else {
            $form->get('email')->setData($senderEmail);
        }

        return $this->render('human_authentications/show.html.twig', [
            'token' => $token,
            'mailToValidate' => $senderEmail,
            'form' => $form,
            'confirm' => $confirm,
            'content' => $content
        ]);
    }
}
