<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\Office365Connector;
use App\Form\Office365ConnectorType;
use App\Message\SynchronizeConnectors;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/office365')]
class Office365ConnectorController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/{domain}/new', name: 'app_connector_o365_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Domain $domain, ConnectorRepository $connectorRepository): Response
    {
        $connector = new Office365Connector();
        $connector->setDomain($domain);
        $form = $this->createForm(Office365ConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_o365_new', ['domain' => $domain->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->save($connector);

            return $this->redirectToRoute('domain_edit', [
                'id' => $domain->getId(),
                '_fragment' => 'auth',
            ]);
        }

        $form->get("type")->setData(ConnectorTypes::OFFICE365);
        return $this->render('connector/new.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_o365_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response
    {
        $form = $this->createForm(Office365ConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_o365_edit', ['id' => $connector->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->save($connector);

            return $this->redirectToRoute('domain_edit', [
                'id' => $connector->getDomain()->getId(),
                '_fragment' => 'auth',
            ]);
        }

        return $this->render('connector/edit.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/office365/{id}/sync-user', name: 'app_office365_connector_sync')]
    public function syncUser(Request $request, Connector $connector): Response
    {
        if ($this->isCsrfTokenValid('sync' . $connector->getId(), $request->query->get('_token'))) {
            $this->bus->dispatch(new SynchronizeConnectors($connector->getId()));

            $this->addFlash('success', new TranslatableMessage('Message.Connector.syncStarted'));
        }

        return $this->redirectToRoute('domain_edit', [
            'id' => $connector->getDomain()->getId(),
            '_fragment' => 'auth',
        ]);
    }
}
