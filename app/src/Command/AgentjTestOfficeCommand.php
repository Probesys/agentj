<?php

namespace App\Command;

use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function GuzzleHttp\json_decode;

#[AsCommand(
            name: 'agentj:test-office',
            description: 'Add a short description for your command',
    )]
class AgentjTestOfficeCommand extends Command {

    protected function configure(): void {
        $this
                ->addArgument('comain', InputArgument::OPTIONAL, 'Domain ')
                ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        $guzzle = new Client();
        $tenantId = 'cbacea7a-c8b5-4559-a2e2-073295800e9c';
        $clientId = 'a25058e2-9845-41f8-ab20-10f5039a8f76';
        $clientSecret = 'wlx8Q~WLK~qCrktgBUKm0iE2vzfQ4Cq0ci0nnaMU';
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $token = json_decode($guzzle->post($url, [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ])->getBody()->getContents());
//        dd($token);
        $this->loadUsers($token);
        $accessToken = $token->access_token;

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
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

        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        $user = $graph->createRequest("GET", '/users/b9b39c23-160b-445c-a90f-f6763998cf15?$select=proxyaddresses')
//                      ->setReturnType(User::class)
                ->execute();
        dd($user->getBody());
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
