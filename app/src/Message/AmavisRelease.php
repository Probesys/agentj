<?php

namespace App\Message;

final class AmavisRelease
{
    public function __construct(
        public readonly string $mailId,
        public readonly int $partitionTag,
        public readonly int $rseqnum,
        public readonly int $finalStatus,
    ) {
    }

    public function getMailId(): string
    {
        return $this->mailId;
    }

    public function getPartitionTag(): int
    {
        return $this->partitionTag;
    }

    public function getRseqnum(): int
    {
        return $this->rseqnum;
    }

    public function getFinalStatus(): int
    {
        return $this->finalStatus;
    }
}
