<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Repository\ConnectorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/connector')]
class ConnectorController extends AbstractController
{
    #[Route('/delete/{id}', name: 'app_connector_delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        Connector $connector,
        ConnectorRepository $connectorRepository,
        TranslatorInterface $translator,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $connector->getId(), $request->query->get('_token'))) {
            $connectorRepository->remove($connector);
            $this->addFlash('success', $translator->trans('Message.Flash.deleteSuccesFull'));
        }

        return $this->redirectToRoute('domain_edit', [
            'id' => $connector->getDomain()->getId(),
        ], Response::HTTP_SEE_OTHER);
    }
}
