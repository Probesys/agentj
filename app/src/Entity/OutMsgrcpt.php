<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\OutMsgrcptRepository;

#[ORM\Table(name: 'out_msgrcpt')]
#[ORM\Index(name: 'out_msgrcpt_idx_mail_id', columns: ['mail_id'])]
#[ORM\Index(name: 'out_msgrcpt_idx_rid', columns: ['rid'])]
#[ORM\Index(name: 'out_msgrcpt_idx_status_id', columns: ['status_id'])]
#[ORM\Entity(repositoryClass: OutMsgrcptRepository::class)]
class OutMsgrcpt extends BaseMessageRecipient
{
    #[ORM\ManyToOne(inversedBy: 'msgRcpts')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private OutMsg $msgs;

    public function getMsgs(): ?OutMsg
    {
        return $this->msgs;
    }

    public function setMsgs(?OutMsg $msgs): self
    {
        $this->msgs = $msgs;

        return $this;
    }
}
