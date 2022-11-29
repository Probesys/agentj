<?php

namespace App\Controller;

use App\Command\Office365ImportCommand;
use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\Office365Connector;
use App\Form\ConnectorType;
use App\Form\Office365ConnectorType;
use App\Model\ConnectorTypes;
use App\Repository\ConnectorRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * @Security("is_granted('ROLE_ADMIN')")
 * @Route("/office365")
 */
class Office365ConnectorController extends AbstractController {

    private Application $application;

    public function __construct(KernelInterface $kernel) {
        $this->application = new Application($kernel);
    }

    
    #[Route('/{domain}/new', name: 'app_connector_o365_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Domain $domain, ConnectorRepository $connectorRepository): Response {

        $connector = new Office365Connector();
        $connector->setDomain($domain);
        $form = $this->createForm(Office365ConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_o365_new', ['domain' => $domain->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $connectorRepository->add($connector, true);

            return $this->redirectToRoute('domain_edit', ['id' => $domain->getId()], Response::HTTP_SEE_OTHER);
        }

        $form->get("type")->setData(ConnectorTypes::Office365);
        return $this->renderForm('connector/new.html.twig', [
                    'connector' => $connector,
                    'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_connector_show', methods: ['GET'])]
    public function show(Office365Connector $connector): Response {
        return $this->render('connector/show.html.twig', [
                    'connector' => $connector,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_connector_o365_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response {
        $form = $this->createForm(Office365ConnectorType::class, $connector, [
            'action' => $this->generateUrl('app_connector_o365_edit', ['id' => $connector->getId()]),
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

    #[Route('/office365/{id}/sync-user', name: 'app_office365_connector_sync')]
    public function syncUser(Request $request, Connector $connector, ConnectorRepository $connectorRepository): Response {
        if ($this->isCsrfTokenValid('sync' . $connector->getId(), $request->query->get('_token'))) {
            $input = new ArrayInput([
                'connectorId' => $connector->getId(),
            ]);
            $command = $this->application->find(Office365ImportCommand::getDefaultName());
            $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
            $command->run($input, $output);
            $this->addFlash('success', $output->fetch());
        }


        return $this->redirectToRoute('domain_edit', ['id' => $connector->getDomain()->getId()]);
    }

}
