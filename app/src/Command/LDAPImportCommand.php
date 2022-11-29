<?php

namespace App\Command;

use App\Entity\Groups;
use App\Entity\LdapConnector;
use App\Entity\User as User;
use App\Service\CryptEncryptService;
use App\Service\MailaddrService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Group as GraphGroup;
use Microsoft\Graph\Model\User as GraphUser;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        if (!$this->connect()){
            $io->error("Cannot connect to LDAP Server");
            return Command::FAILURE;
        }
        ;

                
        $this->importUsers();
//        $this->importGroups($token);

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

        $query = $this->ldap->query("ou=users,dc=easyd,dc=local", $ldapQuery);

        $results = $query->execute();
        foreach ($results as $entry) {

            $user = $this->em->getRepository(User::class)->findOneByUid($entry->getDN());
            $emailAdress = $entry->getAttribute($mailAttribute) ? $entry->getAttribute($mailAttribute)[0] : null;
            $userName = $entry->getAttribute($realNameAttribute) ? $entry->getAttribute($realNameAttribute)[0] : null;
            if (!$emailAdress) {
                continue;
            }
            $isNew = false;
            if (!$user) {
                $user = new User();
                $user->setUid($entry->getDN());
                $user->setPolicy($this->connector->getDomain()->getPolicy());
                $isNew = true;
            }

            $user->setFullname($userName);
            $user->setUsername($emailAdress);
            $user->setEmail($emailAdress);
            $user->setDomain($this->connector->getDomain());
            $user->setRoles('["ROLE_USER"]');

            $this->em->persist($user);
            
            $this->nbUserUpdated = $isNew ? $this->nbUserUpdated : $this->nbUserUpdated = $this->nbUserUpdated + 1;
            $this->nbUserCreated = $isNew ? $this->nbUserCreated  = $this->nbUserCreated + 1 : $this->nbUserCreated;
        }
        $this->em->flush();
    }

    private function addAliases(User $user, array $proxyAdresses): void {
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
    private function importGroups(\stdclass $token): void {
        $graph = new Graph();
        $graph->setAccessToken($token->access_token);
        $domain = $this->connector->getDomain();
        try {
            $groups = $graph->createRequest("GET", '/groups' . '?$filter=endsWith(mail,\'@' . $domain->getDomain() . '\' )&$count=true')
                    ->setReturnType(GraphGroup::class)
                    ->addHeaders(['ConsistencyLevel' => 'eventual'])
                    ->execute();

            foreach ($groups as $m365group) {

                $localGroup = $this->em->getRepository(Groups::class)->findOneByUid($m365group->getId());
                if (!$localGroup) {
                    $localGroup = new Groups();
                    $localGroup->setPriority(1);
                    $localGroup->setName($m365group->getDisplayName());
                    $localGroup->isActive(true);
                    $localGroup->setPolicy($this->connector->getDomain()->getPolicy());
                    $localGroup->setDomain($this->connector->getDomain());
                    $localGroup->setOriginConnector($this->connector);
                    $localGroup->setWb("");
                    $localGroup->setUid($m365group->getId());
                    $this->em->persist($localGroup);
                    $this->em->flush();
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
