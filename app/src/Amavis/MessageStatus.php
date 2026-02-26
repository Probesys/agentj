<?php

namespace App\Amavis;

class MessageStatus
{
    public const BANNED = 1;
    public const AUTHORIZED = 2;
    public const DELETED = 3;
    public const ERROR = 4;
    public const RESTORED = 5;
    public const SPAMMED = 6;
    public const VIRUS = 7;
    public const UNTREATED = 8;

    public static function getStatusName(?int $status): string
    {
        switch ($status) {
            case MessageStatus::UNTREATED:
                return 'Untreated';
            case MessageStatus::BANNED:
                return 'Banned';
            case MessageStatus::AUTHORIZED:
                return 'Authorized';
            case MessageStatus::DELETED:
                return 'Deleted';
            case MessageStatus::ERROR:
                return 'Error';
            case MessageStatus::RESTORED:
                return 'Restored';
            case MessageStatus::SPAMMED:
                return 'Spammed';
            case MessageStatus::VIRUS:
                return 'Virus';
            default:
                return 'Unknown';
        }
    }
}
