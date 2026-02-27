<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MsgrcptRepository;

#[ORM\Table(name: 'msgrcpt')]
#[ORM\Index(name: 'msgrcpt_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'msgrcpt_idx_rid', columns: ['rid'])]
#[ORM\Index(name: 'msgrcpt_idx_bspam_level', columns: ['bspam_level'])]
#[ORM\Entity(repositoryClass: MsgrcptRepository::class)]
class Msgrcpt extends BaseMessageRecipient
{
    #[ORM\ManyToOne(inversedBy: 'msgRcpts')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private Msgs $msgs;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $amavisReleaseStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $amavisReleaseEndedAt = null;

    /**
     * @return Msgs|null
     */
    public function getMsgs(): ?Msgs
    {
        return $this->msgs;
    }

    /**
     * @param Msgs|null $msgs
     * @return $this
     */
    public function setMsgs(?Msgs $msgs): self
    {
        $this->msgs = $msgs;

        return $this;
    }

    public function getAmavisReleaseStartedAt(): ?\DateTimeImmutable
    {
        return $this->amavisReleaseStartedAt;
    }

    public function setAmavisReleaseStartedAt(?\DateTimeImmutable $amavisReleaseStartedAt): static
    {
        $this->amavisReleaseStartedAt = $amavisReleaseStartedAt;

        return $this;
    }

    public function getAmavisReleaseEndedAt(): ?\DateTimeImmutable
    {
        return $this->amavisReleaseEndedAt;
    }

    public function setAmavisReleaseEndedAt(?\DateTimeImmutable $amavisReleaseEndedAt): static
    {
        $this->amavisReleaseEndedAt = $amavisReleaseEndedAt;

        return $this;
    }

    public function isAmavisReleaseOngoing(): bool
    {
        $tenMinutesAgo = (new \DateTimeImmutable())->modify('-10 minutes');

        return !$this->isAlreadyReleased()
            && $this->amavisReleaseStartedAt !== null
            && $this->amavisReleaseStartedAt >= $tenMinutesAgo;
    }
}
