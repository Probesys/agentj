<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\MailaddrRepository;

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
    private ?int $id = null;

    #[ORM\Column(name: 'priority', type: 'integer', nullable: false, options: ['default' => 7])]
    private int $priority = 7;


    #[ORM\Column(name: 'email', type: Types::BINARY, nullable: false)]
    private mixed $email;

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

    public function getEmail(): mixed
    {
        return stream_get_contents($this->email, -1, 0);
    }


    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
