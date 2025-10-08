<?php

namespace App\Command;

use App\Entity\Domain;
use App\Entity\Groups;
use App\Entity\LdapConnector;
use App\Entity\User as User;
use App\Repository\DomainRepository;
use App\Service\GroupService;
use App\Service\LdapService;
use App\Service\MailaddrService;
use App\Service\UserService;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'agentj:import-ldap',
    description: 'import users from LDAP',
)]
class LDAPImportCommand extends Command
{
    private ?LdapConnector $connector;
    private Ldap $ldap;
    private SymfonyStyle $io;
    private int $nbUserUpdated = 0;
    private int $nbUserCreated = 0;
    private int $nbAliasCreated = 0;

    /** @var array<User> */
    private array $newUsers = [];

    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private LdapService $ldapService,
        private UserService $userService,
        private GroupService $groupService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('connectorId', InputArgument::REQUIRED, 'Connector from wich import users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $connectorId = $input->getArgument('connectorId');

        $this->connector = $this->em->getRepository(LdapConnector::class)->find($connectorId);
        if (!$this->connector) {
            $this->io->error('Connector not found');
            return Command::FAILURE;
        }

        try {
            $this->ldap = $this->ldapService->bind($this->connector);
        } catch (ConnectionException $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $this->importUsers();
        if ($this->connector->isSynchronizeGroup()) {
            $this->importGroups();
        }

        $this->assignGroupsToImportedUsers();

        return Command::SUCCESS;
    }

    private function importUsers(): void
    {
        $mailAttribute = $this->connector->getLdapEmailField();
        $aliasAttribute = $this->connector->getLdapAliasField();
        $sharedWithAttribute = $this->connector->getLdapSharedWithField();

        if ($this->connector->getLdapUserFilter()) {
            $ldapQuery = $this->connector->getLdapUserFilter();
            $query = $this->ldap->query($this->connector->getLdapBaseDN(), $ldapQuery);

            $results = $query->execute();
            $this->ldapService->filterUserResultOnDomain($results, $this->connector);

            foreach ($results as $entry) {
                $listEmails = $entry->getAttribute($mailAttribute) ?? [];

                if (count($listEmails) === 0) {
                    continue;
                }

                // We consider that the first email of the list is the main email of the user.
                $emailAddress = $listEmails[0];

                $user = $this->findOrCreateUser($emailAddress);

                if (!$user) {
                    continue;
                }

                $this->updateUserFromLdap($user, $entry, $emailAddress);

                $this->em->persist($user);
                $this->em->flush();

                $listAliases = [];
                if ($aliasAttribute) {
                    $listAliases = $entry->getAttribute($aliasAttribute) ?? [];
                }

                $listAliases = array_merge($listAliases, $listEmails);
                $listAliases = array_unique($listAliases);

                foreach ($listAliases as $aliasEmail) {
                    $this->createAlias($user, $aliasEmail);
                }

                if ($sharedWithAttribute) {
                    $listSharedWith = $entry->getAttribute($sharedWithAttribute) ?? [];
                    $listSharedWith = array_reduce($listSharedWith, function ($carry, $item) {
                        $emails = array_map('trim', explode(',', $item));
                        return array_merge($carry, $emails);
                    }, []);

                    foreach ($listSharedWith as $sharedWithEmail) {
                        $sharedUser = $this->findOrCreateUser($sharedWithEmail);

                        if ($sharedUser) {
                            $sharedUser->addOwnedSharedBox($user);
                        }
                    }
                }
            }
        }

        $this->io->writeln($this->translator->trans('Message.Connector.resultImportUser', [
            '$NB_USER_CREATED' => $this->nbUserCreated,
            '$NB_USER_UPDATED' => $this->nbUserUpdated,
            '$NB_ALIAS_CREATED' => $this->nbAliasCreated,
        ]));

        $this->em->flush();
    }

    private function updateUserFromLdap(
        User $user,
        Entry $entry,
        string $emailAddress
    ): void {
        $realNameAttribute = $this->connector->getLdapRealNameField();
        $attribute = $entry->getAttribute($realNameAttribute);
        $fullname = $attribute ? $attribute[0] : null;

        $user->setLdapDN($entry->getDN());
        $user->setUid($entry->getAttribute('uid')[0]);
        $user->setFullname($fullname);
        $user->setUsername($emailAddress);

        if (!in_array($user, $this->newUsers)) {
            $this->nbUserUpdated++;
        }
    }

    private function findOrCreateUser(string $emailAddress): ?User
    {
        if (!Email::validate($emailAddress)) {
            return null;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $emailAddress]);

        if ($user) {
            return $user;
        }

        // Verify and recover the email domain
        $domainName = Email::extractDomain($emailAddress);

        if (!$domainName) {
            return null;
        }

        $domain = $this->em->getRepository(Domain::class)->findOneByDomain($domainName);

        // If the domain does not exist in AgentJ, ignore the user.
        if (!$domain) {
            return null;
        }

        $user = new User();
        $user->setPolicy($domain->getPolicy());
        $user->setOriginConnector($this->connector);
        $user->setEmail($emailAddress);
        $user->setDomain($domain);
        $user->setPriority(MailaddrService::computePriority($emailAddress));
        $user->setRoles('["ROLE_USER"]');
        $this->em->persist($user);
        $this->em->flush();
        $this->nbUserCreated++;
        $this->newUsers[] = $user;

        return $user;
    }

    private function createAlias(User $user, string $aliasEmail): void
    {
        if (!Email::validate($aliasEmail)) {
            return;
        }

        // Make sure to not mark the base user as an alias of himself
        if ($aliasEmail === $user->getEmail()) {
            return;
        }

        // Alias domain verification
        $domainName = Email::extractDomain($aliasEmail);

        if (!$domainName) {
            return;
        }

        $domain = $this->em->getRepository(Domain::class)->findOneByDomain($domainName);

        // If the domain is not managed, the alias is ignored.
        if (!$domain) {
            return;
        }

        $alias = $this->em->getRepository(User::class)->findOneBy(['email' => $aliasEmail]);
        if (!$alias) {
            $alias = new User();
            $this->nbAliasCreated++;
        }

        $alias->setEmail($aliasEmail);
        $alias->setUsername($aliasEmail);
        $alias->setOriginalUser($user);
        $alias->setDomain($domain);
        $this->em->persist($alias);
        $this->em->flush();
    }

    private function assignGroupsToImportedUsers(): void
    {
        $targetGroups = $this->connector->getTargetGroups();

        if ($targetGroups->isEmpty()) {
            return;
        }

        foreach ($this->connector->getUsers() as $user) {
            foreach ($targetGroups as $group) {
                $user->addGroup($group);
            }

            $this->userService->updateAliasGroupsAndPolicyFromUser($user);
        }
        $this->groupService->updateWblist();
    }

    private function importGroups(): void
    {
        $realNameAttribute = $this->connector->getLdapGroupNameField();
        $groupMemberAttribute = $this->connector->getLdapGroupMemberField();

        if ($this->connector->getLdapGroupFilter()) {
            $ldapQuery = $this->connector->getLdapGroupFilter();
            $query = $this->ldap->query($this->connector->getLdapBaseDN(), $ldapQuery);

            $priorityMax = $this->em->getRepository(Groups::class)
                                    ->getMaxPriorityforDomain($this->connector->getDomain());

            $results = $query->execute();
            $nbGroupUpdated = 0;
            $nbGroupCreated = 0;
            $this->ldapService->filterGroupResultWihtoutMembers($results, $groupMemberAttribute);

            foreach ($results as $ldapGroup) {
                /* @var $ldapGroup Entry */
                $isNew = false;
                $group = $this->em->getRepository(Groups::class)->findOneByLdapDN($ldapGroup->getDN());
                if (!$group) {
                    $group = new Groups();
                    $group->setLdapDN($ldapGroup->getDn());
                    $group->setPolicy($this->connector->getDomain()->getPolicy());
                    $group->setActive(false);
                    $group->setPriority($priorityMax);
                    $group->setOverrideUser(false);
                    $group->setDomain($this->connector->getDomain());
                    $group->setWb("");
                    $group->setOriginConnector($this->connector);
                    $isNew = true;
                    $priorityMax++;
                }

                $group->setName($ldapGroup->getAttribute($realNameAttribute)[0]);
                $this->em->persist($group);
                $nbGroupUpdated = $isNew ? $nbGroupUpdated : $nbGroupUpdated = $nbGroupUpdated + 1;
                $nbGroupCreated = $isNew ? $nbGroupCreated = $nbGroupCreated + 1 : $nbGroupCreated;
                $this->addMembersToLdapGroup($ldapGroup, $group);
            }

            $this->io->writeln($this->translator->trans('Message.Connector.resultImportGroup', [
                '$NB_GROUP_CREATED' => $nbGroupCreated,
                '$NB_GROUP_UPDATED' => $nbGroupUpdated,
            ]));

            $this->em->flush();
        }
    }

    private function addMembersToLdapGroup(Entry $ldapGroup, Groups $group): void
    {
        $members = [];
        $groupMemberfield = $this->connector->getLdapGroupMemberField();
        $members = $ldapGroup->getAttribute($groupMemberfield) ? $ldapGroup->getAttribute($groupMemberfield) : [];
        foreach ($members as $member) {
            $user = $this->em->getRepository(User::class)->findOneByLdapDN($member);
            if ($user) {
                $group->addUser($user);
            }
        }
    }
}
