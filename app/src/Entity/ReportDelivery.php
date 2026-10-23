<?php

namespace App\Entity;

use App\Repository\ReportDeliveryRepository;
use App\Service\ReportMailBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @phpstan-import-type ReportContent from ReportMailBuilder
 */
#[ORM\Entity(repositoryClass: ReportDeliveryRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_report_delivery_user', columns: ['user_id'])]
class ReportDelivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private string $email;

    #[ORM\Column]
    private int $scheduledAt;

    #[ORM\Column]
    private bool $forced;

    #[ORM\Column(nullable: true)]
    private ?int $periodEnd = null;

    /**
     * @var ReportContent|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $content = null;

    public function __construct(User $user, int $scheduledAt, bool $forced = false)
    {
        $this->user = $user;
        $this->email = $user->getEmail() ?? '';
        $this->scheduledAt = $scheduledAt;
        $this->forced = $forced;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getScheduledAt(): int
    {
        return $this->scheduledAt;
    }

    public function isForced(): bool
    {
        return $this->forced;
    }

    public function getPeriodEnd(): ?int
    {
        return $this->periodEnd;
    }

    /**
     * @return ReportContent|null
     */
    public function getContent(): ?array
    {
        return $this->content;
    }

    /**
     * @param ReportContent $content
     */
    public function prepare(array $content, int $periodEnd): void
    {
        $this->content = $content;
        $this->periodEnd = $periodEnd;
    }
}
