<?php

namespace App\Entity;

use App\Repository\SenderRuleRepository;
use Doctrine\DBAL\Schema\DefaultExpression\CurrentTimestamp;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'wblist')]
#[ORM\Entity(repositoryClass: SenderRuleRepository::class)]
class SenderRule
{
    use RuleTrait;

    public const PRIORITY_DOMAIN = 0;
    public const PRIORITY_GROUP = 50;
    public const PRIORITY_USER = 100;
    public const PRIORITY_GROUP_OVERRIDE = 200;

    public const TYPE_USER = 0; // List origin is a user
    public const TYPE_AUTHENTICATION = 1 ; // List origin is human authentication
    public const TYPE_GROUP = 2; // List origin is a group
    public const TYPE_ADMIN = 3; // List origin is an administrator
    public const TYPE_OUTMAIL = 4; // List origin is a sent email
    public const TYPE_IMPORT = 5; // List origin is an import file

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'senderRules')]
    #[ORM\JoinColumn(name: 'rid', nullable: true, onDelete: 'CASCADE')]
    #[ORM\Id]
    private User $user;

    #[ORM\ManyToOne(targetEntity: RuleAddress::class)]
    #[ORM\JoinColumn(name: 'sid', nullable: true)]
    #[ORM\Id]
    private RuleAddress $senderRuleAddress;

    #[ORM\Column(name: 'wb', type: 'string', length: 10, nullable: false)]
    private string $wb;

    #[ORM\Column(name: 'datemod', type: 'datetime', nullable: true, options: ['default' => new CurrentTimestamp()])]
    private ?\DateTimeInterface $datemod ;

    #[ORM\Column(name: 'type', type: 'integer', nullable: true)]
    private ?int $type;

    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', nullable: true, onDelete: 'CASCADE')]
    private ?Group $group;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    private ?int $priority;

    public function __construct(User $user, RuleAddress $ruleAddress)
    {
        $this->user = $user;
        $this->senderRuleAddress = $ruleAddress;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setSenderRuleAddress(RuleAddress $senderRuleAddress): self
    {
        $this->senderRuleAddress = $senderRuleAddress;

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

    public function getSenderRuleAddress(): RuleAddress
    {
        return $this->senderRuleAddress;
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
