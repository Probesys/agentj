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
class ConnectorController extends AbstractController {

    #[Route('/', name: 'app_connector_index', methods: ['GET'])]
    public function index(ConnectorRepository $connectorRepository): Response {
        return $this->render('connector/index.html.twig', [
                    'connectors' => $connectorRepository->findAll(),
        ]);
    }

    

    #[Route('/delete/{id}', name: 'app_connector_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response {
        if ($this->isCsrfTokenValid('delete' . $connector->getId(), $request->query->get('_token'))) {
            $connectorRepository->remove($connector, true);
        }

        return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()], Response::HTTP_SEE_OTHER);
    }

}
