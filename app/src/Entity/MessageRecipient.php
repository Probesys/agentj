<?php

namespace App\Entity;

use App\Repository\MessageRecipientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'msgrcpt')]
#[ORM\Index(name: 'msgrcpt_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'msgrcpt_idx_rid', columns: ['rid'])]
#[ORM\Index(name: 'msgrcpt_idx_bspam_level', columns: ['bspam_level'])]
#[ORM\Entity(repositoryClass: MessageRecipientRepository::class)]
class MessageRecipient extends BaseMessageRecipient
{
    #[ORM\ManyToOne(inversedBy: 'messageRecipients')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private ?Message $message;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $amavisReleaseStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $amavisReleaseEndedAt = null;

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

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
