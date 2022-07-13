<?php

namespace App\Entity;

use App\Entity\Traits\EntityBlameableTrait;
use App\Entity\Traits\EntityTimestampableTrait;
use App\Repository\ConnectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConnectorRepository::class)
 */
class Connector
{
    use EntityBlameableTrait;
    use EntityTimestampableTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity=Domain::class, inversedBy="connectors")
     * @ORM\JoinColumn(nullable=false)
     */
    private $domain;

    /**
     * @ORM\OneToOne(targetEntity=Office365Connector::class, mappedBy="connector", cascade={"persist", "remove"})
     */
    private $office365Connector;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getOffice365Connector(): ?Office365Connector
    {
        return $this->office365Connector;
    }

    public function setOffice365Connector(Office365Connector $office365Connector): self
    {
        // set the owning side of the relation if necessary
        if ($office365Connector->getConnector() !== $this) {
            $office365Connector->setConnector($this);
        }

        $this->office365Connector = $office365Connector;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
