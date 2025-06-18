<?php

namespace App\Amavis;

class ContentType
{
    public const Virus = 'V';
    public const Banned = 'B';
    public const Unchecked = 'U';
    public const Spam = 'S';
    public const Spammy = 'Y';
    public const BadMime = 'M';
    public const BadHeader = 'H';
    public const Oversized = 'O';
    public const MtaError = 'T';
    public const Clean = 'C';
}
