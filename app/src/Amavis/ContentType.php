<?php

namespace App\Amavis;

enum ContentType: string
{
    case Virus = 'V';
    case Banned = 'B';
    case Unchecked = 'U';
    case Spam = 'S';
    case Spammy = 'Y';
    case BadMime = 'M';
    case BadHeader = 'H';
    case Oversized = 'O';
    case MtaError = 'T';
    case Clean = 'C';
}
