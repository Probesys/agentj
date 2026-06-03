<?php

namespace App\Entity;

use App\Repository\MailaddrRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mailaddr
 */
#[ORM\Table(name: 'mailaddr')]
#[ORM\UniqueConstraint(name: 'email', columns: ['email'])]
#[ORM\Entity(repositoryClass: MailaddrRepository::class)]
class Mailaddr
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'priority', type: 'integer', nullable: false, options: ['default' => 7])]
    private int $priority = 7;


    #[ORM\Column(name: 'email', type: Types::BINARY, length: 255, nullable: false)]
    private string $email;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

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
}
