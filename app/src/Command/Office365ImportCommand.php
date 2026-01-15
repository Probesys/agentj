<?php

namespace App\Command;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\Office365Connector;
use App\Entity\User as User;
use App\Service\MailaddrService;
use App\Util\Email;
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
class Office365ImportCommand extends Command
{
    private EntityManagerInterface $em;
    private Office365Connector $connector;
    private TranslatorInterface $translator;
    private SymfonyStyle $io;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        parent::__construct();
        $this->em = $em;
        $this->translator = $translator;
    }

    protected function configure(): void
    {
        $this->addArgument('connectorId', InputArgument::REQUIRED, 'Connector from wich import users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io =  new SymfonyStyle($input, $output);
        $connectorId = $input->getArgument('connectorId');

        $this->connector = $this->em->getRepository(Office365Connector::class)->find($connectorId);
        if (!$this->connector) {
            $this->io->error('Connector not found');
            return Command::FAILURE;
        }

        $token = $this->getToken();
        if (!$token) {
            $this->io->write($this->translator->trans('Message.Office365Connector.tokenError'));
            return Command::FAILURE;
        }

        $this->importUsers($token);

        if ($this->connector->isSynchronizeGroup()) {
            $this->importGroups($token);
        }

        return Command::SUCCESS;
    }

    private function getToken(): ?string
    {
        $guzzle = new Client();
        $clientId = $this->connector->getClientId();
        $clientSecret = $this->connector->getClientSecret();
        $url = 'https://login.microsoftonline.com/' . $this->connector->getTenant() . '/oauth2/v2.0/token';
        try {
            $result = $guzzle->post($url, [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ],
            ])->getBody()->getContents();

            $token = json_decode($result, associative: true);

            if (!$token || !isset($token['access_token'])) {
                return null;
            }

            return $token['access_token'];
        } catch (GuzzleException $exception) {
            return null;
        }
    }

    private function importUsers(string $token): void
    {
        $graph = new Graph();
        $graph->setAccessToken($token);
        $domain = $this->connector->getDomain();
        $users = [];

        try {
            $endpoint = '/users?$select=id,displayName,mail,proxyaddresses, userPrincipalName';
            $endpoint .= '&$filter=endsWith(userPrincipalName,\'@' . $domain->getDomain() . '\' )&$count=true&$top=999';
            $users = $graph->createRequest('GET', $endpoint)
                           ->setReturnType(GraphUser::class)
                           ->addHeaders(['ConsistencyLevel' => 'eventual'])
                           ->execute();
        } catch (GuzzleException $exc) {
            $this->io->error($exc->getMessage());
        }

        $nbUserCreated = 0;
        $nbUserUpdated = 0;
        $nbAliasCreated = 0;
        foreach ($users as $graphUser) {
            /* @var $graphUser GraphUser */
            if (is_null($graphUser->getMail())) {
                continue;
            }

            $user = $this->em->getRepository(User::class)->findOneBy([
                'office365PrincipalName' => $graphUser->getUserPrincipalName(),
            ]);

            if (!$user) {
                $user = $this->em->getRepository(User::class)->findOneBy(['email' => $graphUser->getMail()]);
            }

            if (!$user) {
                $user = new User();
                $user->setEmail($graphUser->getMail());
                $nbUserCreated++;
            } else {
                $nbUserUpdated++;
            }

            $user->setOffice365PrincipalName($graphUser->getUserPrincipalName());
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
                $aliases = $this->addAliases($user, $graphUser->getProxyAddresses());
                $nbAliasCreated += count($aliases);
            }

            $this->em->persist($user);
            $this->em->flush();
        }
        $this->io->writeln($this->translator->trans('Message.Connector.resultImportUser', [
            '$NB_USER_CREATED' => $nbUserCreated,
            '$NB_USER_UPDATED' => $nbUserUpdated,
            '$NB_ALIAS_CREATED' => $nbAliasCreated,
        ]));
    }

    /**
     * Add aliases to a user
     *
     * @param array<string> $proxyAdresses
     * @return User[]
     */
    private function addAliases(User $user, array $proxyAdresses): array
    {
        $aliases = [];
        foreach ($proxyAdresses as $proxyAdress) {
            if (!str_starts_with(strtolower($proxyAdress), 'smtp:')) {
                continue;
            }

            $aliasEmail = substr($proxyAdress, strlen('smtp:'));
            if (!Email::validate($aliasEmail)) {
                continue;
            }

            // Don't mark the initial user as an alias of himself
            if ($aliasEmail === $user->getEmail()) {
                continue;
            }

            // Get the domain associated to the email address
            $domainName = Email::extractDomain($aliasEmail);
            if (!$domainName) {
                continue;
            }

            $domain = $this->em->getRepository(Domain::class)->findOneByDomain($domainName);
            if (!$domain || $domain->getId() !== $this->connector->getDomain()->getId()) {
                continue;
            }

            // Create the alias if it doesn't exist yet
            $alias = $this->em->getRepository(User::class)->findOneBy(['email' => $aliasEmail]);
            if (!$alias) {
                $alias = new User();
                $alias->setOriginConnector($this->connector);
            }

            // And update information
            $alias->setEmail($aliasEmail);
            $alias->setUsername($aliasEmail);
            $alias->setOriginalUser($user);
            $alias->setDomain($domain);

            $this->em->persist($alias);

            $aliases[] = $alias;
        }
        return $aliases;
    }

    private function importGroups(string $token): void
    {
        $graph = new Graph();
        $graph->setAccessToken($token);
        $domain = $this->connector->getDomain();
        $nbGroupCreated = 0;
        $nbGroupUpdated = 0;
        try {
            $endpoint = '/groups' . '?$filter=endsWith(mail,\'@' . $domain->getDomain() . '\' )&$count=true';
            $groups = $graph->createRequest('GET', $endpoint)
                            ->setReturnType(GraphGroup::class)
                            ->addHeaders(['ConsistencyLevel' => 'eventual'])
                            ->execute();
            $priorityMax = $this->em->getRepository(Groups::class)
                                    ->getMaxPriorityforDomain($this->connector->getDomain());

            foreach ($groups as $m365group) {
                $localGroup = $this->em->getRepository(Groups::class)->findOneByUid($m365group->getId());
                if (!$localGroup) {
                    $localGroup = new Groups();
                    $localGroup->setPriority($priorityMax + 1);
                    $localGroup->setName($m365group->getDisplayName());
                    $localGroup->setActive(false);
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
                    $nbGroupCreated++;
                } else {
                    $nbGroupUpdated++;
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
        $this->io->writeln($this->translator->trans('Message.Connector.resultImportGroup', [
                    '$NB_GROUP_CREATED' => $nbGroupCreated,
                    '$NB_GROUP_UPDATED' => $nbGroupUpdated,
        ]));
    }

    private function addMembersToGroup(string $token, Groups $group): void
    {
        $members = [];
        $graph = new Graph();
        $graph->setAccessToken($token);

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
     */
    private function addUserGroupOwners(string $token, User $userGroup): void
    {
        $owners = [];
        $graph = new Graph();
        $graph->setAccessToken($token);

        try {
            $owners = $graph->createRequest("GET", '/groups/' . $userGroup->getUId() . '/owners')
                    ->setReturnType(GraphUser::class)
                    ->execute();
        } catch (GuzzleException $exc) {
        }

        // user created from group
        foreach ($owners as $owner) {
            $user = $this->em->getRepository(User::class)->findOneBy([
                'uid' => $owner->getId(),
                'email' => $owner->getMail(),
            ]);
            if ($user) {
                $userGroup->addSharedWith($user);
            }
        }
    }
}
