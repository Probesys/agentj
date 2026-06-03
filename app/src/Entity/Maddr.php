<?php

namespace App\Entity;

use App\Repository\MaddrRepository;
use App\Util\Url;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Maddr
 */
#[ORM\Table(name: 'maddr')]
#[ORM\Index(name: 'idx_maddr_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'part_email', columns: ['partition_tag', 'email'])]
#[ORM\Entity(repositoryClass: MaddrRepository::class)]
class Maddr
{
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int|string $id;

    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: true)]
    private ?int $partitionTag = 0;

    #[ORM\Column(name: 'email', type: Types::BINARY, length: 255, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'is_invalid', type: 'boolean', nullable: true)]
    private ?bool $isInvalid = null;

    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    private string $domain;

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function setPartitionTag(int $partitionTag): self
    {
        $this->partitionTag = $partitionTag;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     *
     * @return string
     */
    public function getReverseDomain(): string
    {
        return Url::reverseDomainName($this->domain);
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getIsInvalid(): ?bool
    {
        return $this->isInvalid;
    }

    public function setIsInvalid(?bool $isInvalid): self
    {
        $this->isInvalid = $isInvalid;

        return $this;
    }
}
