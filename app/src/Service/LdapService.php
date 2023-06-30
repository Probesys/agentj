<?php

namespace App\Service;

use App\Entity\LdapConnector;
use App\Entity\User;
use App\Repository\GroupsWblistRepository;
use App\Repository\WblistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;

class LdapService {

    private EntityManagerInterface $em;
    private WblistRepository $wblistRepository;
    private GroupsWblistRepository $groupsWblistRepository;
    private CryptEncryptService $cryptEncryptService;

    public function __construct(
            EntityManagerInterface $em,
            WblistRepository $wblistRepository,
            GroupsWblistRepository $groupsWblistRepository,
            CryptEncryptService $cryptEncryptService) {
        $this->em = $em;
        $this->wblistRepository = $wblistRepository;
        $this->cryptEncryptService = $cryptEncryptService;
        $this->groupsWblistRepository = $groupsWblistRepository;
    }

    public function bind(LdapConnector $connector): bool|Ldap {

        $ldap = Ldap::create('ext_ldap', [
                    'host' => $connector->getLdapHost(),
                    'port' => $connector->getLdapPort(),
        ]);
        $baseDN = "";
        if (!$bindDN = $connector->getLdapBindDn()) {
            throw new ConnectionException('Please configure ldap search DN');
        }

        if (!$searchPassword = $connector->getLdapPassword()) {
            throw new ConnectionException('Please configure ldap password');
        }

        try {
            $clearPassword = $this->cryptEncryptService->decrypt($searchPassword)[1];

            $ldap->bind($bindDN, $clearPassword);
            return $ldap;
        } catch (ConnectionException $exception) {
            throw new ConnectionException('Please configure ldap search DN');
        }
    }

    public function bindUser(User $user, string $password) {

        foreach ($user->getDomain()->getConnectors() as $connector) {

            if ($connector instanceof LdapConnector) {
                $ldap = Ldap::create('ext_ldap', [
                            'host' => $connector->getLdapHost(),
                            'port' => $connector->getLdapPort(),
                ]);
                $baseDN = $connector->getLdapBaseDN();
                $dn = $connector->getLdapLoginField() . '=' . $user->getUid() . ',' . $baseDN;
                try {

                    $ldap->bind(null, $password);
                    return true;
                } catch (ConnectionException $exception) {
                    continue;
                }
            }
            return false;
        }
    }

    public function filterUserResultOnDomain(CollectionInterface &$result, LdapConnector $connector): void {

        $result = array_filter($result->toArray(), function ($user) use ($connector) {
            $email = $user->getAttribute($connector->getLdapEmailField()) ? $user->getAttribute($connector->getLdapEmailField())[0] : null;
            $domainName = $email && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? explode('@', $email)[1] : null;

            return $domainName == $connector->getDomain()->getDomain();
        });
    }
    
    
    public function filterGroupResultWihtoutMembers(CollectionInterface &$result, string $groupMemberAttribute): void {

        $result = array_filter($result->toArray(), function ($ldapGroup) use ($groupMemberAttribute) {
            $nbMembers = $ldapGroup->getAttribute($groupMemberAttribute) ? count($ldapGroup->getAttribute($groupMemberAttribute)) : 0;
            return $nbMembers > 0;
        });
    }    

}
