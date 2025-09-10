<?php

namespace App\Entity;

use App\Repository\Office365ConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Office365ConnectorRepository::class)]
class Office365Connector extends Connector
{
    #[ORM\Column(type: 'string', length: 100)]
    private string $tenant;

    #[ORM\Column(type: 'string', length: 100)]
    private string $clientId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $clientSecret;


    public function getTenant(): string
    {
        return $this->tenant;
    }

    public function setTenant(string $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }
}
