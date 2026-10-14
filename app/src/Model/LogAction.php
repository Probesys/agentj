<?php

namespace App\Model;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum LogAction: string implements TranslatableInterface
{
    case Authorized = 'authorized';
    case AuthorizedBatch = 'authorized batch';
    case AuthorizedForDomain = 'authorized for domain';
    case Banned = 'banned';
    case BannedBatch = 'banned batch';
    case BannedForDomain = 'banned for domain';
    case Delete = 'delete';
    case DeleteBatch = 'delete batch';
    case Restore = 'restore';
    case RestoreBatch = 'restore batch';
    case MarkedAsSpam = 'marked as spam';
    case MarkedAsSpamBatch = 'marked as spam batch';
    case MarkedAsHam = 'marked as ham';
    case MarkedAsHamBatch = 'marked as ham batch';
    case DeleteBatchSenderRule = 'delete batch sender rule';
    case AuthenticationRequestSent = 'Authentification request sent';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans("Entities.Log.actions.{$this->name}", locale: $locale);
    }
}
