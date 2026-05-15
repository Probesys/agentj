<?php

namespace App\Entity;

use App\Repository\LdapConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LdapConnectorRepository::class)]
class LdapConnector extends Connector
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapHost = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ldapPort = 389;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapBaseDN = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapPassword = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapRealNameField = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapEmailField = null;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapGroupMemberField = null;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapBindDn = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapUserFilter = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ldapGroupFilter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldapGroupNameField = null;

    #[ORM\Column(nullable: true)]
    private ?bool $allowAnonymousBind = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldapAliasField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ldapSharedWithField = null;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => 'none'])]
    private string $ldapEncryption = 'none';

    #[ORM\Column(type: 'integer', options: ['default' => 3])]
    private int $ldapVersion = 3;


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


    public function getLdapGroupMemberField(): ?string
    {
        return $this->ldapGroupMemberField;
    }

    public function setLdapGroupMemberField(?string $ldapGroupMemberField): self
    {
        $this->ldapGroupMemberField = $ldapGroupMemberField;

        return $this;
    }


    public function getLdapBindDn(): ?string
    {
        return $this->ldapBindDn;
    }

    public function setLdapBindDn(?string $ldapBindDn): self
    {
        $this->ldapBindDn = $ldapBindDn;

        return $this;
    }

    public function getLdapUserFilter(): ?string
    {
        return $this->ldapUserFilter;
    }

    public function setLdapUserFilter(?string $ldapUserFilter): self
    {
        $this->ldapUserFilter = $ldapUserFilter;

        return $this;
    }

    public function getLdapGroupFilter(): ?string
    {
        return $this->ldapGroupFilter;
    }

    public function setLdapGroupFilter(?string $ldapGroupFilter): self
    {
        $this->ldapGroupFilter = $ldapGroupFilter;

        return $this;
    }

    public function getLdapGroupNameField(): ?string
    {
        return $this->ldapGroupNameField;
    }

    public function setLdapGroupNameField(?string $ldapGroupNameField): self
    {
        $this->ldapGroupNameField = $ldapGroupNameField;

        return $this;
    }

    public function isAllowAnonymousBind(): ?bool
    {
        return $this->allowAnonymousBind;
    }

    public function setAllowAnonymousBind(?bool $allowAnonymousBind): static
    {
        $this->allowAnonymousBind = $allowAnonymousBind;

        return $this;
    }

    public function getLdapAliasField(): ?string
    {
        return $this->ldapAliasField;
    }

    public function setLdapAliasField(?string $ldapAliasField): static
    {
        $this->ldapAliasField = $ldapAliasField;

        return $this;
    }

    public function getLdapSharedWithField(): ?string
    {
        return $this->ldapSharedWithField;
    }

    public function setLdapSharedWithField(?string $ldapSharedWithField): static
    {
        $this->ldapSharedWithField = $ldapSharedWithField;

        return $this;
    }

    public function getLdapEncryption(): string
    {
        return $this->ldapEncryption;
    }

    public function setLdapEncryption(string $ldapEncryption): static
    {
        $this->ldapEncryption = $ldapEncryption;

        return $this;
    }

    public function getLdapVersion(): int
    {
        return $this->ldapVersion;
    }

    public function setLdapVersion(int $ldapVersion): static
    {
        $this->ldapVersion = $ldapVersion;

        return $this;
    }
}
