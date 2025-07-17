<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\DBAL\Types\Types;

/**
 * DomainKey
 */
#[ORM\Table(name: 'dkim')]
#[ORM\Entity(repositoryClass: 'App\Repository\DomainKeyRepository')]
#[ORM\Index(name: 'dkim_idx_domain', columns: ['domain_name'])]
#[UniqueEntity('dkim')]
class DomainKey
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: 'App\Entity\Domain', mappedBy: 'domainKeys', cascade: ['persist'])]
    private Domain $domain;

    #[ORM\Column(name: 'domain_name', type: 'string', length: 255, nullable: false)]
    private string $domainName;

    #[ORM\Column(name: 'selector', type: 'string', length: 255, nullable: false)]
    private string $selector;

    #[ORM\Column(name: 'private_key', type: Types::TEXT, nullable: false)]
    private string $privatekey;

    #[ORM\Column(name: 'public_key', type: Types::TEXT, nullable: false)]
    private string $publickey;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    public function setDomainName(string $domainName): self
    {
        $this->domainName = $domainName;
        return $this;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): self
    {
        $this->selector = $selector;
        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publickey;
    }

    public function setPublicKey(string $publickey): self
    {
        $this->publickey = $publickey;
        return $this;
    }

    public function getPrivateKey(): string
    {
        return $this->privatekey;
    }

    public function setPrivateKey(string $privatekey): self
    {
        $this->privatekey = $privatekey;
        return $this;
    }

    public function getDnsEntry(): string
    {
        $pubkey = preg_replace('/(-----(BEGIN|END) PUBLIC KEY-----)|[ \n]/', '', $this->publickey);
        return "v=DKIM1; k=rsa; s={$this->selector}; p={$pubkey}";
    }

    public function getDnsinfo(): string
    {
        $pubkey = preg_replace('/(-----(BEGIN|END) PUBLIC KEY-----)|[ \n]/', '', $this->publickey);
        return "v=DKIM1; k=rsa; p={$pubkey}";
    }
}
