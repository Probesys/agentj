<?php

namespace App\Amavis;

/**
 * A SpamAssassin test (rule) that matched a message, as listed in the
 * "tests" parameter of the X-Spam-Status header.
 */
final readonly class SpamTest
{
    public function __construct(
        public string $name,
        // Null when the header does not include the scores (SpamAssassin native format)
        public ?float $score = null,
    ) {
    }

    public function getIndicator(): ?SpamIndicator
    {
        return SpamIndicator::fromRule($this->name);
    }
}
