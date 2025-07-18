<?php

namespace App\Command;

use App\Entity\Groups;
use App\Entity\LdapConnector;
use App\Entity\User as User;
use App\Service\LdapService;
use App\Service\MailaddrService;
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
    private EntityManagerInterface $em;
    private ?LdapConnector $connector;
    private Ldap $ldap;
    private TranslatorInterface $translator;
    private LdapService $ldapService;
    private SymfonyStyle $io;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        LdapService $ldapService
    ) {
        parent::__construct();
        $this->em = $em;
        $this->translator = $translator;
        $this->ldapService = $ldapService;
    }

    protected function configure(): void
    {
        $this->addArgument('connectorId', InputArgument::REQUIRED, 'Connector from wich import users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $connectorId = $input->getArgument('connectorId');

        /* @var $connector LdapConnector */
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

        return Command::SUCCESS;
    }

    private function importUsers(): void
    {
        $mailAttribute = $this->connector->getLdapEmailField();
        $realNameAttribute = $this->connector->getLdapRealNameField();
        $nbUserUpdated = 0;
        $nbUserCreated = 0;

        if ($this->connector->getLdapUserFilter()) {
            $ldapQuery = $this->connector->getLdapUserFilter();
            $query = $this->ldap->query($this->connector->getLdapBaseDN(), $ldapQuery);

            $results = $query->execute();
            $this->ldapService->filterUserResultOnDomain($results, $this->connector);

            foreach ($results as $entry) {
                $emailAdress = $entry->getAttribute($mailAttribute) ? $entry->getAttribute($mailAttribute)[0] : null;
                $user = $this->em->getRepository(User::class)->findOneBy(['email' => $emailAdress]);
                // we consider that the first element of the email array is the main email of the user

                $attribute = $entry->getAttribute($realNameAttribute);
                $userName = $attribute ? $attribute[0] : null;
                $isNew = false;
                if (!$user) {
                    $user = new User();

                    $user->setPolicy($this->connector->getDomain()->getPolicy());
                    $isNew = true;
                }
                $user->setLdapDN($entry->getDN());
                $user->setUid($entry->getAttribute('uid')[0]);
                $user->setOriginConnector($this->connector);
                $user->setFullname($userName);
                $user->setUsername($emailAdress);
                $user->setEmail($emailAdress);
                $user->setDomain($this->connector->getDomain());
                $user->setPriority(MailaddrService::computePriority($emailAdress));
                $user->setRoles('["ROLE_USER"]');

                $this->em->persist($user);
                $listEmail = $entry->getAttribute($mailAttribute);
                for ($i = 1; $i < count($listEmail); $i++) {
                    $this->createAlias($user, $listEmail[$i]);
                }

                // if a specific field is used for aliases we complete the list of aliases with these attribute
                $aliasAttribute = $this->connector->getLdapAliasField();
                if ($aliasAttribute) {
                    $listAliases = $entry->getAttribute($aliasAttribute) ?? [];
                    foreach ($listAliases as $aliasEntry) {
                        $this->createAlias($user, $aliasEntry);
                    }
                }

                $nbUserUpdated = $isNew ? $nbUserUpdated : $nbUserUpdated = $nbUserUpdated + 1;
                $nbUserCreated = $isNew ? $nbUserCreated = $nbUserCreated + 1 : $nbUserCreated;
            }
        }

        $this->io->writeln($this->translator->trans('Message.Connector.resultImportUser', [
            '$NB_USER_CREATED' => $nbUserCreated,
            '$NB_USER_UPDATED' => $nbUserUpdated,
        ]));

        $this->em->flush();
    }

    private function createAlias(User $user, string $email): void
    {
        $alias = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$alias) {
            $alias = new User();
        }

        $alias->setEmail($email);
        $alias->setUsername($email);
        $alias->setOriginalUser($user);
        $alias->setDomain($user->getDomain());
        $this->em->persist($alias);
        $this->em->flush();
    }

    private function importGroups(): void
    {

        $mailAttribute = $this->connector->getLdapEmailField();
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
                    $isNew = true;
                    $priorityMax++;
                }

                $group->setName($ldapGroup->getAttribute($realNameAttribute)[0]);
                $group->setOriginConnector($this->connector);
                $this->em->persist($group);
                $nbGroupUpdated = $isNew ? $nbGroupUpdated : $nbGroupUpdated = $nbGroupUpdated + 1;
                $nbGroupCreated = $isNew ? $nbGroupCreated = $nbGroupCreated + 1 : $nbGroupCreated;
                $this->addMembersToGroup($ldapGroup, $group);
            }

            $this->io->writeln($this->translator->trans('Message.Connector.resultImportGroup', [
                '$NB_GROUP_CREATED' => $nbGroupCreated,
                '$NB_GROUP_UPDATED' => $nbGroupUpdated,
            ]));

            $this->em->flush();
        }
    }

    private function addMembersToGroup(Entry $ldapGroup, Groups $group): void
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
