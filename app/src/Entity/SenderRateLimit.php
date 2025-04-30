<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SenderRateLimit
 */
#[ORM\Table(name: 'rate_limits')]
#[ORM\Entity(repositoryClass: 'App\Repository\SenderRateLimitRepository')]
class SenderRateLimit
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'limits', type: 'string', length: 255, nullable: false)]
    private string $limits;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLimits(): string
    {
        return $this->limits;
    }

    public function setLimits(string $limits): self
    {
        $this->limits = $limits;
        return $this;
    }
}
