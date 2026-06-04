<?php

namespace App\Amavis;

use Symfony\Component\Translation\TranslatableMessage;

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
    public const UNRELEASED = 9;

    public static function getStatusName(?int $status): string
    {
        switch ($status) {
            case MessageStatus::UNTREATED:
                return 'Untreated';
            case MessageStatus::UNRELEASED:
                return 'Unreleased';
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

    public static function getStatusLabel(?int $status): TranslatableMessage
    {
        switch ($status) {
            case MessageStatus::UNTREATED:
                return new TranslatableMessage('Entities.Message.Status.Untreated');
            case MessageStatus::UNRELEASED:
                return new TranslatableMessage('Entities.Message.Status.Unreleased');
            case MessageStatus::BANNED:
                return new TranslatableMessage('Entities.Message.Status.Banned');
            case MessageStatus::AUTHORIZED:
                return new TranslatableMessage('Entities.Message.Status.Authorized');
            case MessageStatus::DELETED:
                return new TranslatableMessage('Entities.Message.Status.Deleted');
            case MessageStatus::ERROR:
                return new TranslatableMessage('Entities.Message.Status.Error');
            case MessageStatus::RESTORED:
                return new TranslatableMessage('Entities.Message.Status.Restored');
            case MessageStatus::SPAMMED:
                return new TranslatableMessage('Entities.Message.Status.Spam');
            case MessageStatus::VIRUS:
                return new TranslatableMessage('Entities.Message.Status.Virus');
            default:
                return new TranslatableMessage('Entities.Message.Status.Unknown');
        }
    }
}
