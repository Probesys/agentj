<?php

namespace App\Misc;

use Symfony\Component\Translation\TranslatableMessage;

// This file contains translations keys that are built dynamically.
// The translation:extract command cannot find them otherwise, and delete the
// keys from the translations files. By listing them manually in this file, the
// command detects them, even if this file is never used in the application.

new TranslatableMessage('Entities.Log.actions.AuthenticationRequestSent');
new TranslatableMessage('Entities.Log.actions.Authorized');
new TranslatableMessage('Entities.Log.actions.AuthorizedBatch');
new TranslatableMessage('Entities.Log.actions.AuthorizedForDomain');
new TranslatableMessage('Entities.Log.actions.Banned');
new TranslatableMessage('Entities.Log.actions.BannedBatch');
new TranslatableMessage('Entities.Log.actions.BannedForDomain');
new TranslatableMessage('Entities.Log.actions.Delete');
new TranslatableMessage('Entities.Log.actions.DeleteBatch');
new TranslatableMessage('Entities.Log.actions.DeleteBatchSenderRule');
new TranslatableMessage('Entities.Log.actions.MarkedAsHam');
new TranslatableMessage('Entities.Log.actions.MarkedAsHamBatch');
new TranslatableMessage('Entities.Log.actions.MarkedAsSpam');
new TranslatableMessage('Entities.Log.actions.MarkedAsSpamBatch');
new TranslatableMessage('Entities.Log.actions.MessageContentDisplayed');
new TranslatableMessage('Entities.Log.actions.Restore');
new TranslatableMessage('Entities.Log.actions.RestoreBatch');

new TranslatableMessage('Entities.SenderRule.rules.accept');
new TranslatableMessage('Entities.SenderRule.rules.allow');
new TranslatableMessage('Entities.SenderRule.rules.block');
new TranslatableMessage('Entities.SenderRule.rules.enabled');
new TranslatableMessage('Entities.SenderRule.rules.none');

new TranslatableMessage('Entities.SenderRule.actions.deleteSenderB');
new TranslatableMessage('Entities.SenderRule.actions.deleteSenderW');

new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.0');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.1');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.2');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.3');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.4');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelW.5');

new TranslatableMessage('Entities.SenderRule.fields.typeLabelB.0');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelB.1');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelB.2');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelB.3');
new TranslatableMessage('Entities.SenderRule.fields.typeLabelB.5');

new TranslatableMessage('Generics.labels.cancel');

new TranslatableMessage('Message.Dialogs.confirmDeleteSenderContentB');
new TranslatableMessage('Message.Dialogs.confirmDeleteSenderContentW');
new TranslatableMessage('Message.Dialogs.confirmDeleteSenderTitleB');
new TranslatableMessage('Message.Dialogs.confirmDeleteSenderTitleW');
