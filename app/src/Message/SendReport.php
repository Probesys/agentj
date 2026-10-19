<?php

namespace App\Message;

final class SendReport
{
    public function __construct(public readonly int $reportId)
    {
    }
}
