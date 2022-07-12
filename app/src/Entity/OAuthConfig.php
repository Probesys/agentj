<?php

namespace App\Entity;

use App\Repository\OAuthConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OAuthConfigRepository::class)
 */
class OAuthConfig
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tenant_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $client_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $client_secret;

    /**
     * @ORM\OneToOne(targetEntity=Domain::class, inversedBy="oAuthConfig", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $domain;

    public function getId(): ?int
    {
        return $this->id;
    }

   

    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }

    public function setTenantId(string $tenant_id): self
    {
        $this->tenant_id = $tenant_id;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(string $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->client_secret;
    }

    public function setClientSecret(string $client_secret): self
    {
        $this->client_secret = $client_secret;

        return $this;
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
}
