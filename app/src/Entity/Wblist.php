<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Mailaddr;
use App\Repository\WblistRepository;

/**
 * Wblist
 */
#[ORM\Table(name: 'wblist')]
#[ORM\Entity(repositoryClass: WblistRepository::class)]
class Wblist
{
    public const WBLIST_PRIORITY_DOMAIN = 0;
    public const WBLIST_PRIORITY_GROUP = 50;
    public const WBLIST_PRIORITY_USER = 100;
    public const WBLIST_PRIORITY_GROUP_OVERRIDE = 200;

    public const WBLIST_TYPE_USER = 0; // List origin is a user
    public const WBLIST_TYPE_AUTHENTICATION = 1 ; // List origin is human authentication
    public const WBLIST_TYPE_GROUP = 2; // List origin is a group
    public const WBLIST_TYPE_ADMIN = 3; // List origin is an administrator
    public const WBLIST_TYPE_OUTMAIL = 4; // List origin is a sent email
    public const WBLIST_TYPE_IMPORT = 5; // List origin is an import file

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'wbLists')]
    #[ORM\JoinColumn(name: 'rid', nullable: true, onDelete: 'CASCADE')]
    #[ORM\Id]
    private User $rid;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Mailaddr')]
    #[ORM\JoinColumn(name: 'sid', nullable: true)]
    #[ORM\Id]
    private Mailaddr $sid;

    #[ORM\Column(name: 'wb', type: 'string', length: 10, nullable: false)]
    private string $wb;

    #[ORM\Column(name: 'datemod', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $datemod ;

    #[ORM\Column(name: 'type', type: 'integer', nullable: true)]
    private int $type;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Groups')]
    #[ORM\JoinColumn(name: 'group_id', nullable: true, onDelete: 'CASCADE')]
    private Groups $groups;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    private int $priority;

    public function __construct(User $user, Mailaddr $mailaddr)
    {
        $this->rid = $user;
        $this->sid = $mailaddr;
        $this->datemod = new \DateTime();
    }

    public function getWb(): ?string
    {
        return $this->wb;
    }

    public function setWb(string $wb): self
    {
        $this->wb = $wb;

        return $this;
    }

    public function getDatemod(): ?\DateTimeInterface
    {
        return $this->datemod;
    }

    public function setDatemod(\DateTimeInterface $datemod): self
    {
        $this->datemod = $datemod;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRid(): ?User
    {
        return $this->rid;
    }

    public function setRid(?User $rid): self
    {
        $this->rid = $rid;

        return $this;
    }

    public function setSid(Mailaddr $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    public function getGroups(): ?Groups
    {
        return $this->groups;
    }

    public function setGroups(?Groups $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    public function getSid(): Mailaddr
    {
        return $this->sid;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }
}
