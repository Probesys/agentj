<?php

namespace App\Command;

use App\Entity\Domain;
use App\Entity\Office365Connector;
use App\Entity\Groups;
use App\Entity\User;
use App\Service\MailaddrService;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Microsoft\Graph\Generated\Groups\GroupsRequestBuilderGetRequestConfiguration;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Generated\Models\User as GraphUser;
use Microsoft\Graph\Generated\Users\UsersRequestBuilderGetRequestConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
    private ClientCredentialContext $tokenRequestContext;
    private GraphServiceClient $graphServiceClient;

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

        $connector = $this->em->getRepository(Office365Connector::class)->find($connectorId);
        if (!$connector) {
            $this->io->error('Connector not found');
            return Command::FAILURE;
        }
        $this->connector = $connector;

        $clientId = $this->connector->getClientId();
        $clientSecret = $this->connector->getClientSecret();
        $tenantId = $this->connector->getTenant();

        $this->tokenRequestContext = new ClientCredentialContext(
            $tenantId,
            $clientId,
            $clientSecret,
        );

        $this->graphServiceClient = new GraphServiceClient($this->tokenRequestContext);

        $this->importUsers();

        if ($this->connector->isSynchronizeGroup()) {
            $this->importGroups();
        }

        return Command::SUCCESS;
    }

    private function importUsers(): void
    {
        $domain = $this->connector->getDomain();
        $users = [];

        try {
            $requestConfiguration = new UsersRequestBuilderGetRequestConfiguration();
            $headers = [
                'ConsistencyLevel' => 'eventual',
            ];
            $requestConfiguration->headers = $headers;

            $queryParameters = UsersRequestBuilderGetRequestConfiguration::createQueryParameters(
                count: true,
                filter: "endsWith(userPrincipalName,'@" . $domain->getDomain() . "')",
                top: 999,
                select: ['userPrincipalName', 'displayName', 'mail', 'id', 'proxyAddresses'],
            );
            $requestConfiguration->queryParameters = $queryParameters;

            $result = $this->graphServiceClient->users()->get($requestConfiguration)->wait();
            $users = $result->getValue();
        } catch (Exception $exc) {
            $this->io->writeln($exc->getMessage());
        }

        $nbUserCreated = 0;
        $nbUserUpdated = 0;
        $nbAliasCreated = 0;

        foreach ($users as $graphUser) {
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
            'nb_users_created' => $nbUserCreated,
            'nb_users_updated' => $nbUserUpdated,
            'nb_aliases_created' => $nbAliasCreated,
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

    private function importGroups(): void
    {
        $groups = [];
        $domain = $this->connector->getDomain();

        $requestConfiguration = new GroupsRequestBuilderGetRequestConfiguration();
        $headers = [
            'ConsistencyLevel' => 'eventual',
        ];
        $requestConfiguration->headers = $headers;

        $queryParameters = GroupsRequestBuilderGetRequestConfiguration::createQueryParameters(
            count: true,
            select: ['id', 'displayName', 'mail'],
            filter: "endsWith(mail,'@" . $domain->getDomain() . "')",
        );
        $requestConfiguration->queryParameters = $queryParameters;

        $nbGroupCreated = 0;
        $nbGroupUpdated = 0;

        try {
            $result = $this->graphServiceClient->groups()->get($requestConfiguration)->wait();
            $groups = $result->getValue();
        } catch (Exception $exc) {
            $this->io->writeln($exc->getMessage());
        }

        $priorityMax = $this->em->getRepository(Groups::class)->getMaxPriorityforDomain($domain);

        foreach ($groups as $m365group) {
            $localGroup = $this->em->getRepository(Groups::class)->findOneByUid($m365group->getId());

            if (!$localGroup) {
                $localGroup = new Groups();
                $localGroup->setPriority($priorityMax + 1);
                $localGroup->setName($m365group->getDisplayName());
                $localGroup->setActive(false);
                $localGroup->setPolicy($domain->getPolicy());
                $localGroup->setDomain($domain);
                $localGroup->setOriginConnector($this->connector);
                $localGroup->setWbRule('none');
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

            $this->addUserGroupOwners($userGroup);
            $this->addMembersToGroup($localGroup);
        }

        $this->io->writeln($this->translator->trans('Message.Connector.resultImportGroup', [
            'nb_groups_created' => $nbGroupCreated,
            'nb_groups_updated' => $nbGroupUpdated,
        ]));
    }

    /**
     * Get owners of office 365 group. Share the group email with is members
     */
    private function addUserGroupOwners(User $userGroup): void
    {
        $owners = [];

        try {
            $result = $this->graphServiceClient->groups()->byGroupId($userGroup->getUid())->owners()->get()->wait();
            $owners = $result->getValue();
        } catch (Exception $exc) {
            $this->io->writeln($exc->getMessage());
        }

        // user created from group
        foreach ($owners as $owner) {
            if (!$owner instanceof GraphUser) {
                continue;
            }

            $user = $this->em->getRepository(User::class)->findOneBy([
                'uid' => $owner->getId(),
                'email' => $owner->getMail(),
            ]);
            if ($user) {
                $userGroup->addSharedWith($user);
            }
        }
    }

    private function addMembersToGroup(Groups $group): void
    {
        $result = $this->graphServiceClient->groups()->byGroupId($group->getUid())->members()->get()->wait();
        $members = $result->getValue();

        foreach ($members as $member) {
            $user = $this->em->getRepository(User::class)->findOneBy(['uid' => $member->getId()]);
            if ($user) {
                $group->addUser($user);
                $this->em->persist($user);
            }
        }
        $this->em->flush();
    }
}
