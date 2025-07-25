<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Msgrcpt;
use App\Repository\MsgsRepository;

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


    public function __construct()
    {
        $this->msgRcpts = new ArrayCollection();
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
}
