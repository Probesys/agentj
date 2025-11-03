<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MsgrcptRepository;

#[ORM\Table(name: 'msgrcpt')]
#[ORM\Index(name: 'msgrcpt_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'msgrcpt_idx_rid', columns: ['rid'])]
#[ORM\Entity(repositoryClass: MsgrcptRepository::class)]
class Msgrcpt extends BaseMessageRecipient
{
    #[ORM\ManyToOne(inversedBy: 'msgRcpts')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private Msgs $msgs;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $amavisReleaseAt = null;

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

    public function getAmavisReleaseAt(): ?\DateTime
    {
        return $this->amavisReleaseAt;
    }

    public function setAmavisReleaseAt(?\DateTime $amavisReleaseAt): static
    {
        $this->amavisReleaseAt = $amavisReleaseAt;

        return $this;
    }
}
