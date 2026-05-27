<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\LdapConnector;
use App\Form\LdapConnectorType;
use App\Message\SynchronizeConnectors;
use App\Message\HelloMessage;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use App\Service\CryptEncryptService;
use App\Service\LdapService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/ldap')]
class LdapConnectorController extends AbstractController
{
    private Application $application;

    public function __construct(
        private KernelInterface $kernel,
        private TranslatorInterface $translator,
        private CryptEncryptService $cryptEncryptService,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
        $this->application = new Application($kernel);
    }

    #[Route('/{domain}/new', name: 'app_connector_ldap_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Domain $domain, ConnectorRepository $connectorRepository): Response
    {
        $connector = new LdapConnector();
        $connector->setDomain($domain);
        $form = $this->createForm(LdapConnectorType::class, $connector, [
            'action' => $this->generateUrl(
                'app_connector_ldap_new',
                [
                    'domain' => $domain->getId()
                ]
            ),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data->getLdapPassword()) {
                $cryptedPass = $this->cryptEncryptService->encrypt($data->getLdapPassword());
                $data->setLdapPassword($cryptedPass);
            }

            $connectorRepository->save($connector);

            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()], Response::HTTP_SEE_OTHER);
        }

        $form->get("type")->setData(ConnectorTypes::LDAP);
        return $this->render('connector/new_ldap.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_ldap_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LdapConnector $connector, ConnectorRepository $connectorRepository): Response
    {
        $oldPass = $connector->getLdapPassword();

        $form = $this->createForm(LdapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_ldap_edit', ['id' => $connector->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connector = $form->getData();

            if ($connector->getLdapPassword()) {
                $newPass = $this->cryptEncryptService->encrypt($connector->getLdapPassword());
                $connector->setLdapPassword($newPass);
            } else {
                $connector->setLdapPassword($oldPass);
            }

            $connectorRepository->save($connector);

            return $this->redirectToRoute('domain_edit', [
                'id' => $connector->getDomain()->getId(),
                '_fragment' => 'auth',
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('connector/edit_ldap.html.twig', [
            'connector' => $connector,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/sync-user', name: 'app_ldap_connector_sync')]
    public function syncUser(Request $request, Connector $connector): Response
    {
        if ($this->isCsrfTokenValid('sync' . $connector->getId(), $request->query->get('_token'))) {
            $this->bus->dispatch(new SynchronizeConnectors('ldap'));

            // TODO: modify the flash to tell the synchro has begun
            // $this->addFlash('success', nl2br($output->fetch()));

            // TODO: call release_message_controller.js to fetch status
            // Cannot be done at the moment, the controller refresh page without anchor
            // Does this have something to do with Turbo that trim anchor form URL?

            // TODO: add a flash message when import is finished
        }

        $url = $this->generateUrl('domain_edit', ['id' => $connector->getDomain()->getId()]);
        $url .= '#auth';
        $this->logger->error($url);

        return $this->redirect($url);
    }

    #[Route('/checkbind/{domain}', name: 'app_connector_ldap_checkbind', methods: ['GET', 'POST'])]
    public function checkbindAction(Request $request, LdapService $ldapService, Domain $domain): Response
    {
        $testConnector = new LdapConnector();
        $testConnector->setDomain($domain);
        $form = $this->createForm(LdapConnectorType::class, $testConnector);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $testConnector = $form->getData();
            $pass = $request->request->get('encryptedPass');
            if ($testConnector->getLdapPassword()) {
                $pass = $this->cryptEncryptService->encrypt($testConnector->getLdapPassword());
            }

            $testConnector->setLdapPassword($pass);

            try {
                $ldapService->bind($testConnector);
                return new JsonResponse([
                    'status' => 'success',
                    'message' => $this->translator->trans('Message.Flash.connectionOk'),
                ]);
            } catch (ConnectionException $exc) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $this->translator->trans('Message.Flash.connectionKo'),
                ]);
            }
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => $this->translator->trans('Message.Flash.invalidLdapQuery'),
        ]);
    }

    #[Route('/check-users-filter/{domain}', name: 'app_connector_ldap_check_user_filter', methods: ['GET', 'POST'])]
    public function checkUserFilter(Domain $domain, Request $request, LdapService $ldapService): Response
    {

        $testConnector = new LdapConnector();
        $testConnector->setDomain($domain);
        $form = $this->createForm(LdapConnectorType::class, $testConnector);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $testConnector = $form->getData();
            $pass = $request->request->get('encryptedPass');
            if ($testConnector->getLdapPassword()) {
                $pass = $this->cryptEncryptService->encrypt($testConnector->getLdapPassword());
            }

            $testConnector->setLdapPassword($pass);
            $return = [
                'status' => ''
            ];

            try {
                $ldap = $ldapService->bind($testConnector);
                $ldapQuery = $testConnector->getLdapUserFilter() ? $testConnector->getLdapUserFilter() : '';
                $query = $ldap->query($testConnector->getLdapBaseDN(), $ldapQuery);
                try {
                    $results = $query->execute();
                    $results = $ldapService->filterUserResultOnDomain($results, $testConnector);
                    return new JsonResponse([
                        'status' => 'success',
                        'message' => $this->translator->trans('Message.Flash.ldapNbUserfound', [
                            '$NB_USER' => count($results),
                        ]),
                    ]);
                } catch (LdapException $exc) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => $this->translator->trans('Message.Flash.invalidLdapQuery'),
                    ]);
                }
            } catch (ConnectionException $exc) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $this->translator->trans('Message.Flash.connectionKo'),
                ]);
            }
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => $this->translator->trans('Message.Flash.invalidLdapQuery'),
        ]);
    }

    #[Route('/check-groups-filter/{domain}', name: 'app_connector_ldap_check_groups_filter', methods: ['GET', 'POST'])]
    public function checkGroupFilter(Domain $domain, Request $request, LdapService $ldapService): Response
    {

        $testConnector = new LdapConnector();
        $form = $this->createForm(LdapConnectorType::class, $testConnector);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $testConnector = $form->getData();
            $testConnector->setDomain($domain);
            $pass = $request->request->get('encryptedPass');
            if ($testConnector->getLdapPassword()) {
                $pass = $this->cryptEncryptService->encrypt($testConnector->getLdapPassword());
            }

            $testConnector->setLdapPassword($pass);

            try {
                $ldap = $ldapService->bind($testConnector);
                $ldapQuery = $testConnector->getLdapGroupFilter() ? $testConnector->getLdapGroupFilter() : '';
                $query = $ldap->query($testConnector->getLdapBaseDN(), $ldapQuery);
                try {
                    $groups = $query->execute();
                    $groups = $ldapService->filterGroupResultWihtoutMembers(
                        $groups,
                        $testConnector->getLdapGroupMemberField(),
                    );

                    return new JsonResponse([
                        'status' => 'error',
                        'message' => $this->translator->trans('Message.Flash.ldapNbGroupfound', [
                            '$NB_GROUP' => count($groups),
                        ]),
                    ]);
                } catch (LdapException $exc) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => $this->translator->trans('Message.Flash.invalidLdapQuery'),
                    ]);
                }
            } catch (ConnectionException $exc) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $this->translator->trans('Message.Flash.connectionKo'),
                ]);
            }
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => $this->translator->trans('Message.Flash.invalidLdapQuery'),
        ]);
    }
}
