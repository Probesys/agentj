<?php

namespace App\Service;

use App\Entity\LdapConnector;
use App\Entity\User;
use App\Repository\GroupsWblistRepository;
use App\Repository\WblistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
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
            throw new Exception('Please configure ldap search DN');
        }

        if (!$searchPassword = $connector->getLdapPassword()) {
            throw new Exception('Please configure ldap password');
        }

        try {
            $clearPassword = $this->cryptEncryptService->decrypt($searchPassword)[1];
            
            $ldap->bind($bindDN, $clearPassword);
            return $ldap;
        } catch (InvalidCredentialsException $exception) {
            return false;
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

                    $ldap->bind($dn, $password);
                    return true;
                } catch (ConnectionException  $exception) {
                    continue;
                }
            }
            return false;
        }
    }

}
