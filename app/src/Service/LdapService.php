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

    public function __construct(EntityManagerInterface $em, WblistRepository $wblistRepository, GroupsWblistRepository $groupsWblistRepository) {
        $this->em = $em;
        $this->wblistRepository = $wblistRepository;
        $this->groupsWblistRepository = $groupsWblistRepository;
    }

    public function connect(LdapConnector $connector): bool {

        $baseDN = "";
        if (!$baseDN = $connector->getLdapRootDn()) {
            throw new Exception('Please configure ldap search DN');
        }

        if (!$searchPassword = $this->connector->getLdapPassword()) {
            throw new Exception('Please configure ldap password');
        }

        try {

            $clearPassword = $this->cryptEncryptService->decrypt($searchPassword)[1];
            $this->ldap->bind($baseDN, $clearPassword);
            return true;
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
