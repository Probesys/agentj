<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupsWblist
 *
 * @ORM\Table(name="groups_wblist")
 * @ORM\Entity
 */
class GroupsWblist
{

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Groups")
    * @ORM\JoinColumn(name="group_id", nullable=true, onDelete="CASCADE")
    * @ORM\Id
    */
    private $groups;      
    

    /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Mailaddr", fetch="EAGER")
    * @ORM\JoinColumn(name="sid", nullable=true)
    * @ORM\Id
    */
    private $mailaddr;      

    /**
     * @var string
     *
     * @ORM\Column(name="wb", type="string", length=10, nullable=false)
     */
    private $wb;


    public function getWb(): ?string
    {
        return $this->wb;
    }

    public function setWb(string $wb): self
    {
        $this->wb = $wb;

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

    public function getMailaddr(): ?Mailaddr
    {
        return $this->mailaddr;
    }

    public function setMailaddr(?Mailaddr $mailaddr): self
    {
        $this->mailaddr = $mailaddr;

        return $this;
    }

}
