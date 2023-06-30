<?php

namespace App\Controller;

use App\Command\LDAPImportCommand;
use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\LdapConnector;
use App\Form\LdapConnectorType;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use App\Service\CryptEncryptService;
use App\Service\LdapService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ADMIN')")
 * @Route("/ldap")
 */
class LdapConnectorController extends AbstractController {

    private $translator;
    private $em;
    private CryptEncryptService $cryptEncryptService;
    private Application $application;

    public function __construct(KernelInterface $kernel, TranslatorInterface $translator, EntityManagerInterface $em, CryptEncryptService $cryptEncryptService) {
        $this->translator = $translator;
        $this->em = $em;
        $this->cryptEncryptService = $cryptEncryptService;
        $this->application = new Application($kernel);
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
        return $this->renderForm('connector/new_ldap.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_ldap_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LdapConnector $connector, ConnectorRepository $connectorRepository): Response {
        $oldPass = $connector->getLdapPassword();
        $oldPassClear = $this->cryptEncryptService->decrypt($oldPass)[1];

        $form = $this->createForm(LdapConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_ldap_edit', ['id' => $connector->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $connector = $form->getData();
            if (!$connector->getLdapPassword()) {
                $connector->setLdapPassword($oldPass);
            }

            if ($connector->getLdapPassword() && $oldPass !== $connector->getLdapPassword()) {
                $newPass = $this->cryptEncryptService->encrypt($connector->getLdapPassword());
                $connector->setLdapPassword($newPass);
            }
            $connectorRepository->add($connector, true);
            return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connector/edit_ldap.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

    #[Route('/{id}/sync-user', name: 'app_ldap_connector_sync')]
    public function syncUser(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response {
        if ($this->isCsrfTokenValid('sync' . $connector->getId(), $request->query->get('_token'))) {
            $input = new ArrayInput([
                'connectorId' => $connector->getId(),
            ]);
            $command = $this->application->find(LDAPImportCommand::getDefaultName());
            $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
            $command->run($input, $output);
            $this->addFlash('success', nl2br($output->fetch()));
        }


        return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()]);
    }

    #[Route('/checkbind', name: 'app_connector_ldap_checkbind', methods: ['GET', 'POST'])]
    public function checkbindAction(Request $request, LdapService $ldapService): Response {

        $testConnector = new LdapConnector();
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
                return new JsonResponse(['status' => 'success', 'message' => $this->translator->trans('Message.Flash.connectionOk')]);
            } catch (ConnectionException $exc) {
                return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.connectionKo')]);
            }
        }
    }

    #[Route('/check-users-filter/{domain}', name: 'app_connector_ldap_check_user_filter', methods: ['GET', 'POST'])]
    public function checkUserFilter(Domain $domain, Request $request, LdapService $ldapService): Response {

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
            $return = [
                'status' => ''
            ];

            try {
                $ldap = $ldapService->bind($testConnector);
                $ldapQuery = $testConnector->getLdapUserFilter() ? $testConnector->getLdapUserFilter() : '';
                $query = $ldap->query($testConnector->getLdapBaseDN(), $ldapQuery);
                try {
                    $results = $query->execute();
                    $ldapService->filterUserResultOnDomain($results, $testConnector);
                    return new JsonResponse(['status' => 'success', 'message' => $this->translator->trans('Message.Flash.ldapNbUserfound', ['$NB_USER' => count($results)])]);
                } catch (LdapException $exc) {
                    return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.invalidLdapQuery')]);
                }
            } catch (ConnectionException $exc) {
                return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.connectionKo')]);
            }
        }
    }

    #[Route('/check-groups-filter/{domain}', name: 'app_connector_ldap_check_groups_filter', methods: ['GET', 'POST'])]
    public function checkGroupFilter(Domain $domain, Request $request, LdapService $ldapService): Response {

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
            $return = [
                'status' => ''
            ];

            try {
                $ldap = $ldapService->bind($testConnector);
                $ldapQuery = $testConnector->getLdapGroupFilter() ? $testConnector->getLdapGroupFilter() : '';
                $query = $ldap->query($testConnector->getLdapBaseDN(), $ldapQuery);
                try {
                    $groups = $query->execute();
                    $ldapService->filterGroupResultWihtoutMembers($groups, $testConnector->getLdapGroupMemberField());

                    return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.ldapNbGroupfound', ['$NB_GROUP' => count($groups)])]);
                } catch (LdapException $exc) {
                    return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.invalidLdapQuery')]);
                }
            } catch (ConnectionException $exc) {
                return new JsonResponse(['status' => 'error', 'message' => $this->translator->trans('Message.Flash.connectionKo')]);
            }
        }
    }

}
