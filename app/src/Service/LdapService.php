<?php

namespace App\Service;

use App\Entity\LdapConnector;
use App\Entity\User;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;

class LdapService
{
    public function __construct(
        private CryptEncryptService $cryptEncryptService,
    ) {
    }

    public function bind(LdapConnector $connector): Ldap
    {

        $ldap = Ldap::create('ext_ldap', [
                    'host' => $connector->getLdapHost(),
                    'port' => $connector->getLdapPort(),
        ]);

        if ($connector->isAllowAnonymousBind()) {
            try {
                $ldap->bind();
                return $ldap;
            } catch (ConnectionException $exception) {
                throw new ConnectionException('Could not connect to ldap server');
            }
        }

        if (!$bindDN = $connector->getLdapBindDn()) {
            throw new ConnectionException('Please configure ldap search DN');
        }

        if (!$searchPassword = $connector->getLdapPassword()) {
            throw new ConnectionException('Please configure ldap password');
        }

        try {
            $clearPassword = $this->cryptEncryptService->decrypt($searchPassword)[1];
            if ($clearPassword === false) {
                throw new ConnectionException('Could not connect to ldap server');
            }

            $ldap->bind($bindDN, $clearPassword);
            return $ldap;
        } catch (ConnectionException $exception) {
            throw new ConnectionException('Could not connect to ldap server');
        }
    }

    public function bindUser(User $user, string $password): bool
    {
        if (!$user->getLdapDN()) {
            return false;
        }

        $originConnector = $user->getOriginConnector();
        if (!$originConnector instanceof LdapConnector) {
            return false;
        }

        $ldap = Ldap::create('ext_ldap', [
            'host' => $originConnector->getLdapHost(),
            'port' => $originConnector->getLdapPort(),
        ]);

        try {
            $ldap->bind($user->getLdapDN(), $password);
            return true;
        } catch (ConnectionException $exception) {
            return false;
        }
    }

    public function filterUserResultOnDomain(CollectionInterface &$result, LdapConnector $connector): void
    {

        $result = array_filter($result->toArray(), function ($user) use ($connector) {
            $attribute = $user->getAttribute($connector->getLdapEmailField());
            $email = $attribute ? $attribute[0] : null;
            $emailIsValid = $email && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $domainName = $emailIsValid ? explode('@', $email)[1] : null;

            return $domainName == $connector->getDomain()->getDomain();
        });
    }

    public function filterGroupResultWihtoutMembers(CollectionInterface &$result, string $groupMemberAttribute): void
    {

        $result = array_filter($result->toArray(), function ($ldapGroup) use ($groupMemberAttribute) {
            $attribute = $ldapGroup->getAttribute($groupMemberAttribute);
            $nbMembers = $attribute ? count($attribute) : 0;
            return $nbMembers > 0;
        });
    }
}
