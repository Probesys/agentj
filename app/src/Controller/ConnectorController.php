<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Form\ConnectorType;
use App\Repository\ConnectorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/connector')]
class ConnectorController extends AbstractController
{
    #[Route('/', name: 'app_connector_index', methods: ['GET'])]
    public function index(ConnectorRepository $connectorRepository): Response
    {
        return $this->render('connector/index.html.twig', [
            'connectors' => $connectorRepository->findAll(),
        ]);
    }

    #[Route('/{domain}/new', name: 'app_connector_new', methods: ['GET', 'POST'])]
    public function new(Request $request,Domain $domain, ConnectorRepository $connectorRepository): Response
    {

        $connector = new Connector();
        $connector->setDomain($domain);
        $form = $this->createForm(ConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_new', ['domain' => $domain->getId()]),
            'connectorType' => $request->query->get('type')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $connectorRepository->add($connector, true);

            return $this->redirectToRoute('app_connector_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connector/new.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_connector_show', methods: ['GET'])]
    public function show(Connector $connector): Response
    {
        return $this->render('connector/show.html.twig', [
            'connector' => $connector,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response
    {
        $form = $this->createForm(ConnectorType::class, $connector);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->add($connector, true);

            return $this->redirectToRoute('app_connector_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connector/edit.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_connector_delete', methods: ['POST'])]
    public function delete(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$connector->getId(), $request->request->get('_token'))) {
            $connectorRepository->remove($connector, true);
        }

        return $this->redirectToRoute('app_connector_index', [], Response::HTTP_SEE_OTHER);
    }
}
