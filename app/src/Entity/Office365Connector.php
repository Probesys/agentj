<?php

namespace App\Entity;

use App\Repository\Office365ConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Office365ConnectorRepository::class)
 */
class Office365Connector extends Connector
{

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

}
