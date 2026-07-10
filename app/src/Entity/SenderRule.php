<?php

namespace App\Entity;

use App\Repository\SenderRuleRepository;
use Doctrine\DBAL\Schema\DefaultExpression\CurrentTimestamp;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'wblist')]
#[ORM\Entity(repositoryClass: SenderRuleRepository::class)]
class SenderRule
{
    use WbRuleTrait;

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
    private User $rid;

    #[ORM\ManyToOne(targetEntity: RuleAddress::class)]
    #[ORM\JoinColumn(name: 'sid', nullable: true)]
    #[ORM\Id]
    private RuleAddress $sid;

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
        $this->rid = $user;
        $this->sid = $ruleAddress;
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

    public function setSid(RuleAddress $sid): self
    {
        $this->sid = $sid;

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

    public function getSid(): RuleAddress
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
