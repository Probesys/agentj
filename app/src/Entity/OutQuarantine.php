<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'out_quarantine')]
#[ORM\Entity]
class OutQuarantine extends BaseQuarantine
{
    #[ORM\ManyToOne(inversedBy: 'quarantineChunks')]
    #[ORM\JoinColumn(name: 'mail_id', referencedColumnName: 'mail_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'partition_tag', referencedColumnName: 'partition_tag', onDelete: 'CASCADE')]
    private ?OutMessage $message;

    public function getMessage(): OutMessage
    {
        return $this->message;
    }

    public function setMessage(OutMessage $message): self
    {
        $this->message = $message;

        return $this;
    }
}
