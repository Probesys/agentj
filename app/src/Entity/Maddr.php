<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\MaddrRepository;

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
    private ?int $id = null;

    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: true)]
    private int $partitionTag = 0;

    #[ORM\Column(name: 'email', type: Types::BINARY, nullable: false)]
    private mixed $email;

    #[ORM\Column(name: 'is_invalid', type: 'boolean', nullable: true)]
    private ?bool $isInvalid = null;

    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    private string $domain;

    public function getId(): int
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

    public function getEmail(): mixed
    {
        return $this->email;
    }


    public function getEmailClear(): ?string
    {
        $emailClear = stream_get_contents($this->email, -1, 0);
        if ($emailClear === false) {
            return null;
        }
        return $emailClear;
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
        $parts = explode('.', $this->domain);
        return implode('.', array_reverse($parts));
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
