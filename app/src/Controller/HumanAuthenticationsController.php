<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\Wblist;
use App\Form\HumanAuthenticationType;
use App\Repository\MsgsRepository;
use App\Repository\WblistRepository;
use App\Service\HumanAuthenticationService;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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

        $form = $this->createForm(HumanAuthenticationType::class, [
            'email' => $senderEmail,
        ]);
        $form->handleRequest($request);

        $verified = false;

        if ($form->isSubmitted() && $form->isValid()) {
            // Test if the sender is the same than the posted email field and if the mail has not been yet treated
            if (
                !$message->getStatus() &&
                $form->has('email') &&
                $form->get('email')->getData() == $senderEmail &&
                // Test honeypot
                $form->has('emailEmpty') &&
                empty($form->get('emailEmpty')->getData())
            ) {
                // Keep only recipients that are NOT already in a wblist. This avoids,
                // for a instance, a blocked sender to authorise himself.
                $messageRecipients = array_filter(
                    $messageRecipients,
                    function ($recipient) use ($wblistRepository, $sender) {
                        return !$wblistRepository->isRecipientInSenderList($sender, $recipient->getRid());
                    }
                );

                $message->setValidateCaptcha(time());
                $messageService->authorize($message, $messageRecipients, Wblist::WBLIST_TYPE_AUTHENTICATION);

                $verified = true;
            } else {
                $error = new FormError($translator->trans('Message.Flash.checkMailUnsuccessful'));
                $form->get('email')->addError($error);
            }
        }

        return $this->render('human_authentications/show.html.twig', [
            'form' => $form,
            'domain' => $domain,
            'verified' => $verified,
        ]);
    }

    #[Route(
        path: '/portal/domains/{id}/human-authentication-stylesheet.css',
        name: 'human_authentication_stylesheet',
        methods: ['GET'],
    )]
    public function showStylesheet(Domain $domain): Response
    {
        $stylesheet = $domain->getHumanAuthenticationStylesheet();

        $response = new Response($stylesheet);
        $response->headers->set('Content-Type', 'text/css');

        return $response;
    }
}
