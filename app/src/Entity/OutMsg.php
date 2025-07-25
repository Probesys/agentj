<?php

namespace App\Entity;

use App\Entity\BaseMessage;
use App\Repository\OutMsgRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutMsgRepository::class)]
#[ORM\Table(name: 'out_msgs')]
#[ORM\Index(name: 'out_msgs_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'out_msgs_idx_time_iso', columns: ['time_iso'])]
#[ORM\Index(name: 'out_msgs_idx_time_num', columns: ['time_num'])]
#[ORM\Index(name: 'out_msgs_idx_status_id', columns: ['status_id'])]
#[ORM\Index(name: 'out_msgs_idx_message_id', columns: ['message_id'])]
class OutMsg extends BaseMessage
{
    /** @var Collection<int, OutMsgrcpt> $msgRcpts */
    #[ORM\OneToMany(mappedBy: 'msgs', targetEntity: OutMsgrcpt::class)]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag')]
    private Collection $msgRcpts;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $processedUser = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $processedAdmin = false;

    /**
     * @return Collection<int, OutMsgrcpt>
     */
    public function getMsgRcpts(): Collection
    {
        return $this->msgRcpts;
    }

    public function addMsgRcpt(OutMsgrcpt $msgRcpt): self
    {
        if (!$this->msgRcpts->contains($msgRcpt)) {
            $this->msgRcpts[] = $msgRcpt;
            $msgRcpt->setMsgs($this);
        }

        return $this;
    }

    public function removeMsgRcpt(OutMsgrcpt $msgRcpt): self
    {
        if ($this->msgRcpts->removeElement($msgRcpt)) {
            // set the owning side to null (unless already changed)
            if ($msgRcpt->getMsgs() === $this) {
                $msgRcpt->setMsgs(null);
            }
        }

        return $this;
    }
    public function isProcessedUser(): ?bool
    {
        return $this->processedUser;
    }

    public function setProcessedUser(bool $processedUser): self
    {
        $this->processedUser = $processedUser;
        return $this;
    }

    public function isProcessedAdmin(): ?bool
    {
        return $this->processedAdmin;
    }

    public function setProcessedAdmin(bool $processedAdmin): self
    {
        $this->processedAdmin = $processedAdmin;
        return $this;
    }
}
