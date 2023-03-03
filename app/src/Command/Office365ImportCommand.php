<?php

namespace App\Command;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Office365Connector;
use App\Entity\User as User;
use App\Service\MailaddrService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Group as GraphGroup;
use Microsoft\Graph\Model\User as GraphUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
            name: 'agentj:import-office365',
            description: 'import Office 365 users using Micorsoft Graph APi',
    )]
class Office365ImportCommand extends Command {

    private EntityManagerInterface $em;
    private Office365Connector $connector;
    private int $nbUserCreated = 0;
    private int $nbUserUpdated = 0;
    private $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator) {
        parent::__construct();
        $this->em = $em;
        $this->translator = $translator;
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
        $this->connector = $this->em->getRepository(Office365Connector::class)->find($connectorId);
        if (!$this->connector) {
            $io->error('Connector not found');
            return Command::FAILURE;
        }

        $token = $this->getToken();
        if (!$token) {
            $io->write($this->translator->trans('Message.Office365Connector.tokenError'));
            return Command::FAILURE;
        }

//        
        $this->importUsers($token);
        $this->importGroups($token);

        $io->write($this->translator->trans('Message.Office365Connector.resultImport', [
                    '$NB_CREATED' => $this->nbUserCreated,
                    '$NB_UPDATED' => $this->nbUserUpdated,
        ]));
        return Command::SUCCESS;
    }

    private function getToken(): ?\stdClass {
        $guzzle = new Client();
        $clientId = $this->connector->getClient();
        $clientSecret = $this->connector->getClientSecret();
        $url = 'https://login.microsoftonline.com/' . $this->connector->getTenant() . '/oauth2/v2.0/token';
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
            return null;
        }
    }

    private function importUsers(\stdclass $token) {
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);
        $domain = $this->connector->getDomain();

        try {
            $users = $graph->createRequest("GET", '/users' . '?$select=id,displayName,mail,proxyaddresses&$filter=endsWith(userPrincipalName,\'@' . $domain->getDomain() . '\' )&$count=true')
                    ->setReturnType(GraphUser::class)
                    ->addHeaders(['ConsistencyLevel' => 'eventual'])
                    ->execute();
        } catch (GuzzleException $exc) {
            return false;
        }

        foreach ($users as $graphUser) {
            /* @var $graphUser GraphUser */
            if (is_null($graphUser->getMail())) {
                continue;
            }

            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $graphUser->getMail()]);

            if (!$user) {
                $user = new User();
                $user->setEmail($graphUser->getMail());
                $this->nbUserCreated++;
            } else {
                $this->nbUserUpdated++;
            }
            
            $user->setUsername($graphUser->getMail());
            $user->setFullname($graphUser->getDisplayName());
            $user->setReport(true);
            $user->setRoles('["ROLE_USER"]');
            $user->setDomain($domain);
            $user->setUid($graphUser->getId());
            $user->setPolicy($domain->getPolicy());
            $user->setOriginConnector($this->connector);            
            $user->setPriority(MailaddrService::computePriority($graphUser->getMail()));
            if (count($graphUser->getProxyAddresses()) > 1) {
                $this->addAliases($user, $graphUser->getProxyAddresses());
            }

            $this->em->persist($user);
            $this->em->flush();
        }
    }

    private function addAliases(User $user, array $proxyAdresses):void {
        foreach ($proxyAdresses as $proxyAdresse) {
            if (strpos($proxyAdresse, "smtp") !== false) {

                $aliasEmail = explode('smtp:', $proxyAdresse)[1];
                $domainAlias = explode('@', $aliasEmail)[1];
                if ($domainAlias == $this->connector->getDomain()->getDomain()) {
                    $alias = $this->em->getRepository(User::class)->findOneBy(['email' => $aliasEmail]);
                    if (!$alias) {
                        $alias = clone $user;
                    }
                    $alias->setEmail($aliasEmail);
                    $alias->setUserName($aliasEmail);
                    $alias->setOriginalUser($user);
                    $alias->setOriginConnector($this->connector);
                    $this->em->persist($alias);
                }
            }
        }
    }

    /**
     * 
     * @param \stdclass $token
     * @return void
     */
    private function importGroups(\stdclass $token):void {
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);
        $domain = $this->connector->getDomain();
        try {
            $groups = $graph->createRequest("GET", '/groups' . '?$filter=endsWith(mail,\'@' . $domain->getDomain() . '\' )&$count=true')
                    ->setReturnType(GraphGroup::class)
                    ->addHeaders(['ConsistencyLevel' => 'eventual'])
                    ->execute();
            $priorityMax = $this->em->getRepository(Groups::class)->getMaxPriorityforDomain($this->connector->getDomain());
            foreach ($groups as $m365group) {

                $localGroup = $this->em->getRepository(Groups::class)->findOneByUid($m365group->getId());
                if (!$localGroup) {
                    $localGroup = new Groups();
                    $localGroup->setPriority($priorityMax + 1);
                    $localGroup->setName($m365group->getDisplayName());
                    $localGroup->isActive(false);
                    $localGroup->setPolicy($this->connector->getDomain()->getPolicy());
                    $localGroup->setDomain($this->connector->getDomain());
                    $localGroup->setOriginConnector($this->connector);
                    $localGroup->setWb("");
                    $localGroup->setUid($m365group->getId());
                    $this->em->persist($localGroup);
                    $this->em->flush();
                    $priorityMax++;
                }
                /* @var $group GraphGroup */
                $userGroup = $this->em->getRepository(User::class)->findOneByUid($m365group->getId());
                if (!$userGroup) {
                    $userGroup = new User();
                    $userGroup->setEmail($m365group->getMail());
                    $this->nbUserCreated++;
                } else {
                    $this->nbUserUpdated++;
                }
                $userGroup->setUsername($m365group->getMail());
                $userGroup->setFullname($m365group->getDisplayName());
                $userGroup->setReport(true);
                $userGroup->setRoles('["ROLE_USER"]');
                $userGroup->setDomain($domain);
                $userGroup->setUid($m365group->getId());
                $userGroup->setPolicy($domain->getPolicy());
                $userGroup->setPriority(MailaddrService::computePriority($m365group->getMail()));
                $this->em->persist($userGroup);
                $this->em->flush();
                $this->addUserGroupOwners($token, $userGroup);
                $this->addMembersToGroup($token, $localGroup);
            }
        } catch (GuzzleException $exc) {
            
        }
    }

    /**
     * 
     * @param \stdclass $token
     * @param Groups $group
     * @return void
     */
    private function addMembersToGroup(\stdclass $token, Groups $group): void {
        $members = [];
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        try {
            $members = $graph->createRequest("GET", '/groups/' . $group->getUid() . '/members')
                    ->setReturnType(GraphUser::class)
                    ->execute();
        } catch (GuzzleException $exc) {
            
        }

        foreach ($members as $member) {
            $user = $this->em->getRepository(User::class)->findOneBy(['uid' => $member->getId()]);
            if ($user) {

                $group->addUser($user);
                $this->em->persist($user);
            }
        }
        $this->em->flush();
    }

    /**
     * Get owners of office 365 group. Share the group email with is members
     * @param \stdclass $token
     * @param User $userGroup
     * @return void
     */
    private function addUserGroupOwners(\stdclass $token, User $userGroup): void {
        $owners = [];
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);

        try {
            $owners = $graph->createRequest("GET", '/groups/' . $userGroup->getUId() . '/owners')
                    ->setReturnType(GraphUser::class)
                    ->execute();
        } catch (GuzzleException $exc) {
            
        }

        // user created from group
        foreach ($owners as $owner) {
            $user = $this->em->getRepository(User::class)->findOneBy(['uid' => $owner->getId(), 'email' => $owner->getMail()]);
            if ($user) {
                $userGroup->addSharedWith($user);
            }
        }
    }

}
