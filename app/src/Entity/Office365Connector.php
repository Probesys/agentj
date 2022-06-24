<?php

namespace App\Entity;

use App\Repository\Office365ConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Office365ConnectorRepository::class)
 */
class Office365Connector
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $tenant;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $client;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $clientSecret;

    /**
     * @ORM\OneToOne(targetEntity=Connector::class, inversedBy="office365Connector", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $connector;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenant(): ?string
    {
        return $this->tenant;
    }

    public function setTenant(string $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getConnector(): ?Connector
    {
        return $this->connector;
    }

    public function setConnector(Connector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

}
