<?php

namespace App\Command;

use App\Entity\Groups;
use App\Entity\LdapConnector;
use App\Entity\User as User;
use App\Service\CryptEncryptService;
use App\Service\LdapService;
use App\Service\MailaddrService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User as GraphUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
            name: 'agentj:import-ldap',
            description: 'import users from LDAP',
    )]
class LDAPImportCommand extends Command {

    private EntityManagerInterface $em;
    private ?LdapConnector $connector;
    private Ldap $ldap;
    private int $nbUserCreated = 0;
    private int $nbUserUpdated = 0;
    private int $nbGroupCreated = 0;
    private int $nbGroupUpdated = 0;
    private $translator;
    private LdapService $ldapService;

    public function __construct(
            EntityManagerInterface $em, 
            TranslatorInterface $translator,             
            LdapService $ldapService) {
        parent::__construct();
        $this->em = $em;
        $this->translator = $translator;
        $this->ldapService = $ldapService;
    }

    protected function configure(): void {
        $this
                ->addArgument('connectorId', InputArgument::REQUIRED, 'Connector from wich import users')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $connectorId = $input->getArgument('connectorId');

        /* @var $connector LdapConnector */
        $this->connector = $this->em->getRepository(LdapConnector::class)->find($connectorId);
        if (!$this->connector) {
            $io->error('Connector not found');
            return Command::FAILURE;
        }

        if (!$this->ldap = $this->ldapService->bind($this->connector)) {
            $io->error("Cannot connect to LDAP Server");
            return Command::FAILURE;
        }
        ;

        $this->importUsers();
        $this->importGroups();

        $io->write($this->translator->trans('Message.Office365Connector.resultImport', [
                    '$NB_CREATED' => $this->nbUserCreated,
                    '$NB_UPDATED' => $this->nbUserUpdated,
                    '$NB_GROUP_UPDATED' => $this->nbGroupUpdated,
                    '$NB_GROUP_CREATED' => $this->nbGroupCreated,
        ]));
        return Command::SUCCESS;
    }

    

    private function importUsers() {
        $mailAttribute = $this->connector->getLdapEmailField();
        $realNameAttribute = $this->connector->getLdapRealNameField();

        
        if ($this->connector->getLdapUserFilter()) {
            $ldapQuery = $this->connector->getLdapUserFilter();
            $query = $this->ldap->query($this->connector->getLdapBaseDN(), $ldapQuery);

            $results = $query->execute();
            foreach ($results as $entry) {

                $user = $this->em->getRepository(User::class)->findOneByLdapDN($entry->getDN());
                $emailAdress = $entry->getAttribute($mailAttribute) ? $entry->getAttribute($mailAttribute)[0] : null;

                $userName = $entry->getAttribute($realNameAttribute) ? $entry->getAttribute($realNameAttribute)[0] : null;

                if (!$emailAdress || (!filter_var($emailAdress, FILTER_VALIDATE_EMAIL))) {
                    continue;
                }

                //check if same domain
                $domainAdress = explode('@', $emailAdress)[1];

                if ($domainAdress !== $this->connector->getDomain()->getDomain()) {
                    continue;
                }

                $isNew = false;
                if (!$user) {
                    $user = new User();
                    $user->setLdapDN($entry->getDN());
                    $user->setPolicy($this->connector->getDomain()->getPolicy());
                    $isNew = true;
                }
                $user->setUid($entry->getAttribute('uid')[0]);
                $user->setOriginConnector($this->connector);
                $user->setFullname($userName);
                $user->setUsername($emailAdress);
                $user->setEmail($emailAdress);
                $user->setDomain($this->connector->getDomain());
                $user->setPriority(MailaddrService::computePriority($emailAdress));
                $user->setRoles('["ROLE_USER"]');

                $this->em->persist($user);

                $this->nbUserUpdated = $isNew ? $this->nbUserUpdated : $this->nbUserUpdated = $this->nbUserUpdated + 1;
                $this->nbUserCreated = $isNew ? $this->nbUserCreated = $this->nbUserCreated + 1 : $this->nbUserCreated;
            }
        }


        $this->em->flush();
    }

    /**
     * 
     * @param \stdclass $token
     * @return void
     */
    private function importGroups(): void {

        $mailAttribute = $this->connector->getLdapEmailField();
        $realNameAttribute = $this->connector->getLdapRealNameField();

        if ($this->connector->getLdapGroupFilter()) {
            $ldapQuery = $this->connector->getLdapGroupFilter();
            $query = $this->ldap->query($this->connector->getLdapBaseDN(), $ldapQuery);

            $results = $query->execute();
            foreach ($results as $ldapGroup) {
                $nbMembers = $ldapGroup->getAttribute('member') ? count($ldapGroup->getAttribute('member')) : 0;
                if ($nbMembers > 0) {
                    /* @var $ldapGroup Entry */
                    $isNew = false;
                    $group = $this->em->getRepository(Groups::class)->findOneByLdapDN($ldapGroup->getDN());
                    if (!$group) {
                        $group = new Groups();
                        $group->setLdapDN($ldapGroup->getDn());
                        $group->setPolicy($this->connector->getDomain()->getPolicy());
                        $group->setActive(true);
                        $group->setPriority(1);
                        $group->setOverrideUser(false);
                        $group->setDomain($this->connector->getDomain());
                        $group->setWb("");
                        $isNew = true;
                    }
                    $group->setName($ldapGroup->getAttribute('cn')[0]);
                    $group->setOriginConnector($this->connector);
                    $this->em->persist($group);
                    $this->nbGroupUpdated = $isNew ? $this->nbGroupUpdated : $this->nbGroupUpdated = $this->nbGroupUpdated + 1;
                    $this->nbGroupCreated = $isNew ? $this->nbGroupCreated = $this->nbGroupCreated + 1 : $this->nbGroupCreated;
                    $this->addMembersToGroup($ldapGroup, $group);
                }
            }

            $this->em->flush();
        }
    }


    /**
     * 
     * @param \stdclass $token
     * @param Groups $group
     * @return void
     */
    private function addMembersToGroup(Entry $ldapGroup, Groups $group): void {

        $members = [];
        $groupMemberfield = $this->connector->getLdapGroupMemberField();
        $members = $ldapGroup->getAttribute($groupMemberfield) ? $ldapGroup->getAttribute($groupMemberfield) : [];
//        dump($members);
        foreach ($members as $member) {
            $user = $this->em->getRepository(User::class)->findOneByLdapDN($member);
            if ($groupMemberfield == 'memberUid') {
                $user = $this->em->getRepository(User::class)->findOneByUid($member);
            }

            if ($user) {
                $group->addUser($user);
            }
        }
    }

}
