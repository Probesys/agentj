<?php

namespace App\Entity;

use App\Repository\GroupsWblistRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * GroupsWblist
 */
#[ORM\Table(name: 'groups_wblist')]
#[ORM\Entity(repositoryClass: GroupsWblistRepository::class)]
class GroupsWblist
{
    use WbRuleTrait;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Group', inversedBy: 'groupsWbLists')]
    #[ORM\JoinColumn(name: 'group_id', nullable: true, onDelete: 'CASCADE')]
    #[ORM\Id]
    private Group $group;


    #[ORM\ManyToOne(targetEntity: 'App\Entity\Mailaddr', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'sid', nullable: true)]
    #[ORM\Id]
    private Mailaddr $mailaddr;

    /**
     * @var string
     */
    #[ORM\Column(name: 'wb', type: 'string', length: 10, nullable: false)]
    private string $wb;


    public function getWb(): string
    {
        return $this->wb;
    }

    public function setWb(string $wb): self
    {
        $this->wb = $wb;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): self
    {
        $this->group = $group;

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
