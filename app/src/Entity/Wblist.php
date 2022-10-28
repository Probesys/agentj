<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Mailaddr;

/**
 * Wblist
 *
 * @ORM\Table(name="wblist")
 * @ORM\Entity(repositoryClass="App\Repository\WblistRepository")
 */
class Wblist
{
    const WBLIST_PRIORITY_DOMAIN = 0;
    const WBLIST_PRIORITY_GROUP = 50;
    const WBLIST_PRIORITY_USER = 100;
    const WBLIST_PRIORITY_GROUP_OVERRIDE = 200;
    
    const WBLIST_TYPE_USER = 0 ; // Web liste origin is user        
    const WBLIST_TYPE_SENDER = 1 ; // Web liste origin is sender via human authentication
    const WBLIST_TYPE_GROUP = 2; // Web list origin is group


    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="wbLists")
    * @ORM\JoinColumn(name="rid", nullable=true, onDelete="CASCADE")
    * @ORM\Id
    */
    private $rid;

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Mailaddr")
    * @ORM\JoinColumn(name="sid", nullable=true)
    * @ORM\Id
    */
    private $sid;

    /**
     * @var string
     *
     * @ORM\Column(name="wb", type="string", length=10, nullable=false)
     */
    private $wb;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datemod", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $datemod ;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Groups")
    * @ORM\JoinColumn(name="group_id", nullable=true, onDelete="CASCADE")
    */
    private $groups;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;

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

    public function setSid(?Maddr $sid): self
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

    public function getSid(): ?Mailaddr
    {
        return $this->sid;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }
}
