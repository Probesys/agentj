<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * DomainKey
 */
#[ORM\Table(name: 'dkim')]
#[ORM\Entity(repositoryClass: 'App\Repository\DomainKeyRepository')]
#[ORM\Index(name: 'dkim_idx_domain', columns: ['domain_name'])]
#[UniqueEntity('dkim')]
class DomainKey
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Domain
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\Domain', mappedBy: 'domain_keys', cascade: ['persist'])]
    private $domain;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain_name', type: 'string', length: 255, nullable: false)]
    private $domain_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'selector', type: 'string', length: 255, nullable: false)]
    private $selector;

    /**
     * @var text
     */
    #[ORM\Column(name: 'private_key', type: 'text', nullable: false)]
    private $privatekey;

    /**
     * @var text
     */
    #[ORM\Column(name: 'public_key', type: 'text', nullable: false)]
    private $publickey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomainName(): ?string
    {
	    return $this->domain_name;
    }

    public function setDomainName(string $domain_name): self
    {
	    $this->domain_name = $domain_name;
	    return $this;
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): self
    {
        $this->selector = $selector;
        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publickey;
    }

    public function setPublicKey(string $publickey): self
    {
        $this->publickey = $publickey;
        return $this;
    }

    public function getPrivateKey(): ?string
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
}
