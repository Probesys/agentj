<?php

namespace App\Entity;

use App\Repository\GroupRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'groups_wblist')]
#[ORM\Entity(repositoryClass: GroupRuleRepository::class)]
class GroupRule
{
    use RuleTrait;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Group', inversedBy: 'groupRules')]
    #[ORM\JoinColumn(name: 'group_id', nullable: true, onDelete: 'CASCADE')]
    #[ORM\Id]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\RuleAddress', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'sid', nullable: true)]
    #[ORM\Id]
    private RuleAddress $ruleAddress;

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

    public function getRuleAddress(): ?RuleAddress
    {
        return $this->ruleAddress;
    }

    public function setRuleAddress(?RuleAddress $ruleAddress): self
    {
        $this->ruleAddress = $ruleAddress;

        return $this;
    }
}
