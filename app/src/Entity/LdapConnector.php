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
    private $ldapHost;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ldapPort = 389;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapBaseDN;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapPassword;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapGroupField;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapLoginField;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapRealNameField;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapEmailField;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapRootDn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ldapGroupMemberField;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userFilter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $groupFilter;



    public function getLdapHost(): ?string
    {
        return $this->ldapHost;
    }

    public function setLdapHost(?string $ldapHost): self
    {
        $this->ldapHost = $ldapHost;

        return $this;
    }

    public function getLdapPort(): ?int
    {
        return $this->ldapPort;
    }

    public function setLdapPort(?int $ldapPort): self
    {
        $this->ldapPort = $ldapPort;

        return $this;
    }

    public function getLdapBaseDN(): ?string
    {
        return $this->ldapBaseDN;
    }

    public function setLdapBaseDN(?string $ldapBaseDN): self
    {
        $this->ldapBaseDN = $ldapBaseDN;

        return $this;
    }

    public function getLdapPassword(): ?string
    {
        return $this->ldapPassword;
    }

    public function setLdapPassword(?string $ldapPassword): self
    {
        $this->ldapPassword = $ldapPassword;

        return $this;
    }

    public function getLdapGroupFieldName(): ?string
    {
        return $this->ldapGroupField;
    }

    public function setLdapGroupFieldName(?string $ldapGroupField): self
    {
        $this->ldapGroupField = $ldapGroupField;

        return $this;
    }

    public function getLdapLoginField(): ?string
    {
        return $this->ldapLoginField;
    }

    public function setLdapLoginField(?string $ldapLoginField): self
    {
        $this->ldapLoginField = $ldapLoginField;

        return $this;
    }

    public function getLdapGroupField(): ?string
    {
        return $this->ldapGroupField;
    }

    public function setLdapGroupField(?string $ldapGroupField): self
    {
        $this->ldapGroupField = $ldapGroupField;

        return $this;
    }

    public function getLdapRealNameField(): ?string
    {
        return $this->ldapRealNameField;
    }

    public function setLdapRealNameField(?string $ldapRealNameField): self
    {
        $this->ldapRealNameField = $ldapRealNameField;

        return $this;
    }

    public function getLdapEmailField(): ?string
    {
        return $this->ldapEmailField;
    }

    public function setLdapEmailField(?string $ldapEmailField): self
    {
        $this->ldapEmailField = $ldapEmailField;

        return $this;
    }

    public function getLdapRootDn(): ?string
    {
        return $this->ldapRootDn;
    }

    public function setLdapRootDn(?string $ldapRootDn): self
    {
        $this->ldapRootDn = $ldapRootDn;

        return $this;
    }

    public function getLdapGroupMemberField(): ?string
    {
        return $this->ldapGroupMemberField;
    }

    public function setLdapGroupMemberField(?string $ldapGroupMemberField): self
    {
        $this->ldapGroupMemberField = $ldapGroupMemberField;

        return $this;
    }

    public function getUserFilter(): ?string
    {
        return $this->userFilter;
    }

    public function setUserFilter(?string $userFilter): self
    {
        $this->userFilter = $userFilter;

        return $this;
    }

    public function getGroupFilter(): ?string
    {
        return $this->groupFilter;
    }

    public function setGroupFilter(string $groupFilter): self
    {
        $this->groupFilter = $groupFilter;

        return $this;
    }

}
