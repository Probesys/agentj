<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mailaddr
 */
#[ORM\Table(name: 'mailaddr')]
#[ORM\UniqueConstraint(name: 'email', columns: ['email'])]
#[ORM\Entity]
class Mailaddr
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'priority', type: 'integer', nullable: false, options: ['default' => 7])]
    private $priority = '7';

    /**
     * @var binary
     */
    #[ORM\Column(name: 'email', type: 'binary', nullable: false)]
    private $email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getEmail()
    {
        return stream_get_contents($this->email, -1, 0);
    }


    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }
}
