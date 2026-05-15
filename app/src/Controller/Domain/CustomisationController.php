<?php

namespace App\Controller\Domain;

use App\Entity\Domain;
use App\Entity\User;
use App\Form\DomainCustomisationGeneralType;
use App\Form\DomainCustomisationHumanAuthenticationType;
use App\Form\DomainCustomisationReportType;
use App\Repository\DomainRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomisationController extends AbstractController
{
    public function __construct(
        private DomainRepository $domainRepository,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/domain/{id}/customisation', name: 'domain_customisation_general', methods: ['GET', 'POST'])]
    public function general(Request $request, Domain $domain, FileUploader $fileUploader): Response
    {
        $this->checkAccess($domain);

        $form = $this->createForm(DomainCustomisationGeneralType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domain = $form->getData();

            $uploadedFile = $form->get('logoFile')->getData();
            if ($uploadedFile) {
                $uploadedLogo = $fileUploader->upload($uploadedFile);
                $domain->setLogo($uploadedLogo);
            }

            $this->domainRepository->save($domain);

            $this->addFlash('success', $this->translator->trans('Message.Flash.saved'));

            return $this->redirectToRoute('domain_customisation_general', [
                'id' => $domain->getId(),
            ]);
        }

        return $this->render('domain/customisation/general.html.twig', [
            'domain' => $domain,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/domain/{id}/customisation/human-authentication',
        name: 'domain_customisation_human_authentication',
        methods: ['GET', 'POST'],
    )]
    public function humanAuthentication(Request $request, Domain $domain): Response
    {
        $this->checkAccess($domain);

        $form = $this->createForm(DomainCustomisationHumanAuthenticationType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domain = $form->getData();

            $this->domainRepository->save($domain);

            $this->addFlash('success', $this->translator->trans('Message.Flash.saved'));

            return $this->redirectToRoute('domain_customisation_human_authentication', [
                'id' => $domain->getId(),
            ]);
        }

        return $this->render('domain/customisation/human_authentication.html.twig', [
            'domain' => $domain,
            'form' => $form,
            // phpcs:disable Generic.Files.LineLength
            'availableVariables' => [
                'URL_HUMAN_AUTHENTICATION' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.URL_HUMAN_AUTHENTICATION'),
                'EMAIL_DEST' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.EMAIL_DEST'),
            ],
            // phpcs:enable Generic.Files.LineLength
        ]);
    }

    #[Route(path: '/domain/{id}/customisation/report', name: 'domain_customisation_report', methods: ['GET', 'POST'])]
    public function report(Request $request, Domain $domain): Response
    {
        $this->checkAccess($domain);

        $form = $this->createForm(DomainCustomisationReportType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domain = $form->getData();

            $this->domainRepository->save($domain);

            $this->addFlash('success', $this->translator->trans('Message.Flash.saved'));

            return $this->redirectToRoute('domain_customisation_report', [
                'id' => $domain->getId(),
            ]);
        }

        return $this->render('domain/customisation/report.html.twig', [
            'domain' => $domain,
            'form' => $form,
            // phpcs:disable Generic.Files.LineLength
            'availableVariables' => [
                'USERNAME' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.USERNAME'),
                'URL_MSGS' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.URL_MSGS'),
                'NB_UNTREATED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_UNTREATED_MESSAGES'),
                'NB_AUTHORIZED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_AUTHORIZED_MESSAGES'),
                'NB_SPAMMED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_SPAMMED_MESSAGES'),
                'NB_BANNED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_BANNED_MESSAGES'),
                'NB_DELETED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_DELETED_MESSAGES'),
                'NB_RESTORED_MESSAGES' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.NB_RESTORED_MESSAGES'),
                'LIST_MAIL_MSGS' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.LIST_MAIL_MSGS'),
                'FOREACH_MESSAGE' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.FOREACH_MESSAGE'),
                'ENDFOREACH_MESSAGE' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.ENDFOREACH_MESSAGE'),
                'MESSAGE_SENDER' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.MESSAGE_SENDER'),
                'MESSAGE_SUBJECT' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.MESSAGE_SUBJECT'),
                'URL_MESSAGE_AUTHORIZE_SENDER' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.URL_MESSAGE_AUTHORIZE_SENDER'),
                'URL_MESSAGE_RESTORE' => new TranslatableMessage('Entities.Domain.labels.dynamicsVariables.URL_MESSAGE_RESTORE'),
            ],
            // phpcs:enable Generic.Files.LineLength
        ]);
    }

    private function checkAccess(Domain $domain): void
    {
        /** @var User $user */
        $user = $this->getUser();

        $hasAccess = (
            $user->isSuperAdmin() ||
            ($user->isAdmin() && $user->hasDomain($domain))
        );

        if (!$hasAccess) {
            throw new AccessDeniedException();
        }
    }
}
