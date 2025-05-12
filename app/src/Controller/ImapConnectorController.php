<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\ImapConnector;
use App\Form\ImapConnectorType;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/imap')]
class ImapConnectorController extends AbstractController
{
    #[Route('/connector', name: 'app_imap_connector')]
    public function index(): Response
    {
        return $this->render('imap_connector/index.html.twig', [
            'controller_name' => 'ImapConnectorController',
        ]);
    }
    
    #[Route('/{domain}/new', name: 'app_connector_imap_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Domain $domain, ConnectorRepository $connectorRepository): Response {

        $connector = new ImapConnector();
        $connector->setDomain($domain);
        $form = $this->createForm(ImapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_imap_new', ['domain' => $domain->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->add($connector, true);
            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()], Response::HTTP_SEE_OTHER);
        }

        $form->get("type")->setData(ConnectorTypes::IMAP);
        $form->remove("synchronizeGroup");
        return $this->render('connector/new_imap.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_imap_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ImapConnector $connector, ConnectorRepository $connectorRepository): Response {

        $form = $this->createForm(ImapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_imap_edit', ['id' => $connector->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connector = $form->getData();
            $connectorRepository->add($connector, true);
            return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()], Response::HTTP_SEE_OTHER);
        }

        $form->remove("synchronizeGroup");
        return $this->render('connector/edit_imap.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }
    
}
