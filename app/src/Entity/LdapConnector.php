<?php

namespace App\Entity;

use App\Repository\LdapConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LdapConnectorRepository::class)
 */
class LdapConnector extends Connector
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $LdapHost;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $LdapPort;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $LdapBaseDN;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $LdapPassword;


    public function getLdapHost(): ?string
    {
        return $this->LdapHost;
    }

    public function setLdapHost(?string $LdapHost): self
    {
        $this->LdapHost = $LdapHost;

        return $this;
    }

    public function getLdapPort(): ?int
    {
        return $this->LdapPort;
    }

    public function setLdapPort(?int $LdapPort): self
    {
        $this->LdapPort = $LdapPort;

        return $this;
    }

    public function getLdapBaseDN(): ?string
    {
        return $this->LdapBaseDN;
    }

    public function setLdapBaseDN(?string $LdapBaseDN): self
    {
        $this->LdapBaseDN = $LdapBaseDN;

        return $this;
    }

    public function getLdapPassword(): ?string
    {
        return $this->LdapPassword;
    }

    public function setLdapPassword(?string $LdapPassword): self
    {
        $this->LdapPassword = $LdapPassword;

        return $this;
    }
}
