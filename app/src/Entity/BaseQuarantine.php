<?php

namespace App\Entity;

use App\Util\ResourceHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class BaseQuarantine
{
    #[ORM\Column(name: 'partition_tag', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $partitionTag = 0;

    #[ORM\Column(name: 'mail_id', type: Types::BINARY, length: 255, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $mailId; /** @phpstan-ignore property.onlyRead */

    #[ORM\Column(name: 'chunk_ind', type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $chunkInd = 0;

    #[ORM\Column(name: 'mail_text', type: 'blob', length: 65535, nullable: false)]
    private mixed $mailText = null;

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function getMailId(): string
    {
        return $this->mailId;
    }

    public function getChunkInd(): int
    {
        return $this->chunkInd;
    }

    public function getMailText(): ?string
    {
        return ResourceHelper::toString($this->mailText);
    }
}
