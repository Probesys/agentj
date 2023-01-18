<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Maddr
 */
#[ORM\Table(name: 'maddr')]
#[ORM\UniqueConstraint(name: 'part_email', columns: ['partition_tag', 'email'])]
#[ORM\Entity]
class Maddr
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'bigint', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: true)]
    private $partitionTag = '0';

    /**
     * @var binary
     */
    #[ORM\Column(name: 'email', type: 'binary', nullable: false)]
    private $email;

    /**
     * @var int
     */
    #[ORM\Column(name: 'is_invalid', type: 'boolean', nullable: true)]
    private $isInvalid;

    /**
     * @var string
     */
    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: false)]
    private $domain;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartitionTag(): ?int
    {
        return $this->partitionTag;
    }

    public function setPartitionTag(?int $partitionTag): self
    {
        $this->partitionTag = $partitionTag;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }


    public function getEmailClear()
    {
        return stream_get_contents($this->email, -1, 0);
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
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
