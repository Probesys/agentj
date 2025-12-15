<?php

namespace App\Entity;

use App\Entity\Msgrcpt;
use App\Repository\MsgsRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Webklex\PHPIMAP\Message as Email;

/**
 * Msgs
 */
#[ORM\Table(name: 'msgs')]
#[ORM\Index(name: 'msgs_idx_sid', columns: ['sid'])]
#[ORM\Index(name: 'msgs_idx_mess_id', columns: ['message_id'])]
#[ORM\Index(name: 'msgs_idx_time_num', columns: ['time_num'])]
#[ORM\Index(name: 'msgs_idx_time_iso', columns: ['time_iso'])]
#[ORM\Index(name: 'msgs_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'idx_msgs_quar_type', columns: ['quar_type'])]
#[ORM\Entity(repositoryClass: MsgsRepository::class)]
class Msgs extends BaseMessage
{
    /** @var Collection<int, MsgRcpt> $msgRcpts */
    #[ORM\OneToMany(mappedBy: 'msgs', targetEntity: MsgRcpt::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $msgRcpts;

    /** @var Collection<int, Quarantine> $quarantineChunks */
    #[ORM\OneToMany(mappedBy: 'message', targetEntity: Quarantine::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $quarantineChunks;

    public function __construct()
    {
        $this->msgRcpts = new ArrayCollection();
        $this->quarantineChunks = new ArrayCollection();
    }

    /**
     * @return Collection<int, MsgRcpt>
     */
    public function getMsgRcpts(): Collection
    {
        return $this->msgRcpts;
    }

    public function addMsgRcpt(MsgRcpt $msgRcpt): self
    {
        if (!$this->msgRcpts->contains($msgRcpt)) {
            $this->msgRcpts[] = $msgRcpt;
            $msgRcpt->setMsgs($this);
        }

        return $this;
    }

    public function removeMsgRcpt(MsgRcpt $msgRcpt): self
    {
        if ($this->msgRcpts->removeElement($msgRcpt)) {
            // set the owning side to null (unless already changed)
            if ($msgRcpt->getMsgs() === $this) {
                $msgRcpt->setMsgs(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Quarantine>
     */
    public function getQuarantineChunks(): Collection
    {
        return $this->quarantineChunks;
    }

    public function isInQuarantine(): bool
    {
        return !$this->quarantineChunks->isEmpty();
    }

    public function getQuarantineContent(): string
    {
        $rawMail = "";

        foreach ($this->getQuarantineChunks() as $chunk) {
            $rawMail .= $chunk->getMailText() ?? '';
        }

        return mb_convert_encoding($rawMail, 'UTF-8', 'auto');
    }

    public function getQuarantineEmail(): Email
    {
        return Email::fromString($this->getQuarantineContent());
    }
}
