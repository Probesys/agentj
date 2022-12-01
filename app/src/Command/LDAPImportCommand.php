<?php

namespace App\Command;

use App\Entity\Groups;
use App\Entity\LdapConnector;
use App\Entity\User as User;
use App\Service\CryptEncryptService;
use App\Service\MailaddrService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Group as GraphGroup;
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
    private $translator;
    private CryptEncryptService $cryptEncryptService;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, CryptEncryptService $cryptEncryptService) {
        parent::__construct();
        $this->em = $em;
        $this->translator = $translator;
        $this->cryptEncryptService = $cryptEncryptService;
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

        $this->ldap = Ldap::create('ext_ldap', [
                    'host' => $this->connector->getLdapHost(),
                    'port' => $this->connector->getLdapPort(),
        ]);
        if (!$this->connect()) {
            $io->error("Cannot connect to LDAP Server");
            return Command::FAILURE;
        }
        ;

        $this->importUsers();
        $this->importGroups();

        $io->write($this->translator->trans('Message.Office365Connector.resultImport', [
                    '$NB_CREATED' => $this->nbUserCreated,
                    '$NB_UPDATED' => $this->nbUserUpdated,
        ]));
        return Command::SUCCESS;
    }

    public function connect() {

        $baseDN = "";
        if (!$baseDN = $this->connector->getLdapRootDn()) {
            throw new \Exception('Please configure ldap search DN');
        }

        if (!$searchPassword = $this->connector->getLdapPassword()) {
            throw new \Exception('Please configure ldap password');
        }



        try {

            $clearPassword = $this->cryptEncryptService->decrypt($searchPassword)[1];
            $this->ldap->bind($baseDN, $clearPassword);
            return true;
        } catch (InvalidCredentialsException $exception) {
            return false;
        }
    }

    private function importUsers() {
        $mailAttribute = $this->connector->getLdapEmailField();
        $realNameAttribute = $this->connector->getLdapRealNameField();

        $ldapQuery = "(&(objectClass=organizationalPerson)(" . $mailAttribute . "=*))";

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
            
            if ($domainAdress !== $this->connector->getDomain()->getDomain()){
                continue;
            }
            dump($domainAdress);
            
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
            $user->setRoles('["ROLE_USER"]');

            $this->em->persist($user);

            $this->nbUserUpdated = $isNew ? $this->nbUserUpdated : $this->nbUserUpdated = $this->nbUserUpdated + 1;
            $this->nbUserCreated = $isNew ? $this->nbUserCreated = $this->nbUserCreated + 1 : $this->nbUserCreated;
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

        $ldapQuery = "(|(objectClass=posixGroup)(objectClass=group))";

        $query = $this->ldap->query('cn=groups,cn=accounts,dc=idm,dc=probesys,dc=net', $ldapQuery);

        $results = $query->execute();
        foreach ($results as $ldapGroup) {
            $nbMembers = $ldapGroup->getAttribute('member') ?  count($ldapGroup->getAttribute('member')) : 0;
            if ($nbMembers > 0){
            /*@var $ldapGroup Entry */
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

            }            
            $group->setName($ldapGroup->getAttribute('cn')[0]);
            $group->setOriginConnector($this->connector);
            $this->em->persist($group);
            
            $this->addMembersToGroup($ldapGroup, $group);
            }
            
            
        }
  
        $this->em->flush();
    }

    private function loadLdapGroup(string $dn){
        
    }
    /**
     * 
     * @param \stdclass $token
     * @param Groups $group
     * @return void
     */
    private function addMembersToGroup(Entry $ldapGroup, Groups $group): void {
        
        $members=[];
        $groupMemberfield = $this->connector->getLdapGroupMemberField();
        $members = $ldapGroup->getAttribute($groupMemberfield) ? $ldapGroup->getAttribute($groupMemberfield) : [];
//        dump($members);
        foreach($members as $member){
            $user = $this->em->getRepository(User::class)->findOneByLdapDN($member);
            if ($groupMemberfield == 'memberUid'){
                $user = $this->em->getRepository(User::class)->findOneByUid($member);
            }
            
            if ($user){
                $group->addUser($user);
            }
        }
        
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
