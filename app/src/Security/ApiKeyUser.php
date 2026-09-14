<?php

namespace App\Security;

use App\Entity\Domain;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Represents a domain authenticated through the generic user-import API
 * (see ApiKeyAuthenticator). Not backed by the `users` table: this is the
 * domain's own API identity, scoped to that single domain.
 */
class ApiKeyUser implements UserInterface
{
    public function __construct(private Domain $domain)
    {
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        $identifier = $this->domain->getDomain();

        if (!$identifier) {
            throw new \LogicException('Domain name (used as API identifier) cannot be empty');
        }

        return $identifier;
    }
}
