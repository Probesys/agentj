<?php

namespace App\Misc;

use Symfony\Component\Translation\TranslatableMessage;

// This file contains translations keys that are built dynamically.
// The translation:extract command cannot find them otherwise, and delete the
// keys from the translations files. By listing them manually in this file, the
// command detects them, even if this file is never used in the application.

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
