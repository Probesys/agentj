<?php

namespace App\Entity;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The WbRuleTrait helps to manipulate the wblist.wb field (and other similar
 * fields like Groups.wb and GroupsWblist.wb).
 *
 * It abstracts the "wb" value meaning behind the "rule" concept. A rule tells
 * what must be done and can be converted to a wb value. In the other way, a wb
 * value is mapped to a single rule.
 *
 * The possible rules are:
 *
 * - accept (wb = ' '): the sender is known, his emails are analyzed
 *   and delivered if not considered as spam.
 * - block (wb = B): the sender is blocked by Amavis without executing the antispam.
 * - allow (wb = W): the sender is allowed by Amavis without executing the antispam.
 * - enabled (wb = 0): used at the domain level only to represent that the
 *   antispam is enabled for the domain.
 * - none (wb = ''): used at the group level only so no wblist is created by
 *   default for the users of the group.
 *
 * This trait expects that the `getWb()` and `setWb()` methods exist in the
 * base class.
 *
 * @phpstan-type WbRule 'accept'|'block'|'allow'|'enabled'|'none'
 */
trait WbRuleTrait
{
    /**
     * @return WbRule
     */
    public function getWbRule(): string
    {
        return match ($this->getWb()) {
            'W', 'Y' => 'allow',
            'B', 'N' => 'block',
            '0' => 'enabled',
            '' => 'none',
            default => 'accept',
        };
    }

    public function getWbRuleName(): TranslatableMessage
    {
        $wbRule = $this->getWbRule();
        return new TranslatableMessage("Entities.WBList.rules.{$wbRule}");
    }

    /**
     * @param WbRule $wbRule
     */
    public function setWbRule(string $wbRule): self
    {
        $wb = match ($wbRule) {
            'accept' => ' ',
            'allow' => 'W',
            'block' => 'B',
            'enabled' => '0',
            'none' => '',
        };

        return $this->setWb($wb);
    }
}
