<?php

namespace App\Amavis;

class ContentType
{
    public const VIRUS = 'V';
    public const BANNED = 'B';
    public const UNCHECKED = 'U';
    public const SPAM = 'S';
    public const SPAMMY = 'Y';
    public const BAD_MIME = 'M';
    public const BAD_HEADER = 'H';
    public const OVERSIZED = 'O';
    public const MTA_ERROR = 'T';
    public const CLEAN = 'C';
}
