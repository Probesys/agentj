<?php

namespace App\Command;

use App\Entity\Domain;
use App\Entity\Office365Connector;
use App\Entity\User as User;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User as graphUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
            name: 'agentj:import-office365',
            description: 'import Office 365 users using Micorsoft Graph APi',
    )]
class Office365ImportCommand extends Command {

    private EntityManagerInterface $em;
    private string $tenant;

    public function __construct(EntityManagerInterface $em) {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void {
        $this
                ->addArgument('connectorId', InputArgument::REQUIRED, 'Connector from wich import users')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $connectorId = $input->getArgument('connectorId');

        /* @var $connector Office365Connector */
        $connector = $this->em->getRepository(Office365Connector::class)->find($connectorId);
        if (!$connector) {
            $io->error('Connector not found');
            return Command::FAILURE;
        }

        $this->tenant = $connector->getTenant();

        $token = $this->getToken($connector);
        if (!$token) {
            $io->error('Unable to get access token from graph API. Please check your parameters');
            return Command::FAILURE;
        }
        $this->loadUsers($token);
        $accessToken = $token->access_token;

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

    private function getToken(Office365Connector $connector): ?\stdClass {
        $guzzle = new Client();
        $clientId = $connector->getClient();
        $clientSecret = $connector->getClientSecret();
        $url = 'https://login.microsoftonline.com/' . $this->tenant . '/oauth2/v2.0/token';
        try {
            $token = json_decode($guzzle->post($url, [
                        'form_params' => [
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'scope' => 'https://graph.microsoft.com/.default',
                            'grant_type' => 'client_credentials',
                        ],
                    ])->getBody()->getContents());

            return $token;
        } catch (GuzzleException $exception) {
//            $io->error('Unable to connect with this parameters' . $exception->getMessage());
            return null;
        }
    }

    private function loadGroups($token) {

        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        $user = $graph->createRequest("GET", '/users/b9b39c23-160b-445c-a90f-f6763998cf15?$select=proxyaddresses')
//                      ->setReturnType(User::class)
                ->execute();
        dd($user->getBody());
        echo "Hello, I am {$user->getGivenName()}.";
    }

    private function loadUsers($token) {
//        dd($token);
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        $result = $graph->createRequest("GET", '/users')
//                      ->setReturnType(User::class)
                ->execute();
        $users = $result->getBody()['value'];
        foreach ($users as $graphUser) {
            /* @var $graphUser graphUser */
            $user = new User();
            $user->setEmail($graphUser['mail']);
            dump($graphUser);
        }
        die;
        echo "Hello, I am {$user->getGivenName()}.";
    }

    private function loadAliases($userId) {

        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        $user = $graph->createRequest("GET", '/users/' . $userId . '?$select=proxyaddresses')
//                      ->setReturnType(User::class)
                ->execute();
        dd($user->getBody());
        echo "Hello, I am {$user->getGivenName()}.";
    }

}
