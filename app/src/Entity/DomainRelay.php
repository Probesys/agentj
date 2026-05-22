<?php

namespace App\Entity;

use App\Repository\DomainRelayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainRelayRepository::class)]
#[ORM\Table(name: 'domain_relay')]
class DomainRelay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $ipAddress;

    #[ORM\ManyToOne(inversedBy: 'domainRelays')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Domain $domain;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }
}
