<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\LdapConnector;
use App\Form\LdapConnectorType;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use App\Service\CryptEncryptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/ldap')]
class LdapConnectorController extends AbstractController {

    private $translator;
    private $em;
    private CryptEncryptService $cryptEncryptService;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, CryptEncryptService $cryptEncryptService) {
        $this->translator = $translator;
        $this->em = $em;
        $this->cryptEncryptService = $cryptEncryptService;
    }

    #[Route('/{domain}/new', name: 'app_connector_ldap_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Domain $domain, ConnectorRepository $connectorRepository): Response {

        $connector = new LdapConnector();
        $connector->setDomain($domain);
        $form = $this->createForm(LdapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_ldap_new', ['domain' => $domain->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $cryptedPass = $this->cryptEncryptService->encrypt($data->getLdapPassword());
            $data->setLdapPassword($cryptedPass);
            $connectorRepository->add($connector, true);

            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()], Response::HTTP_SEE_OTHER);
        }

        $form->get("type")->setData(ConnectorTypes::LDAP);
        return $this->renderForm('connector/new.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_ldap_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response {
        $form = $this->createForm(LdapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_ldap_edit', ['id' => $connector->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->add($connector, true);

            return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connector/edit.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

}
