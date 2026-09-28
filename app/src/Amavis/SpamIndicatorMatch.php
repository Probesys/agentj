<?php

namespace App\Amavis;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * A spam indicator and the SpamAssassin tests that triggered it on a message.
 */
final readonly class SpamIndicatorMatch
{
    /**
     * @param list<SpamTest> $tests
     */
    public function __construct(
        public SpamIndicator $indicator,
        public array $tests,
    ) {
    }

    public function getLabel(): TranslatableMessage
    {
        return $this->indicator->getLabel();
    }

    /**
     * Return the sum of the tests scores, or null if the scores are unknown.
     */
    public function getScore(): ?float
    {
        $score = null;

        foreach ($this->tests as $test) {
            if ($test->score !== null) {
                $score = ($score ?? 0.0) + $test->score;
            }
        }

        return $score !== null ? round($score, 3) : null;
    }

    /**
     * Return the signed score (e.g. "+1.5", "-0.001"), or an empty string if unknown.
     */
    public function getFormattedScore(): string
    {
        $score = $this->getScore();

        if ($score === null) {
            return '';
        }

        $formattedScore = rtrim(rtrim(sprintf('%+.3f', $score), '0'), '.');

        return in_array($formattedScore, ['+0', '-0'], true) ? '0' : $formattedScore;
    }

    public function increasesScore(): bool
    {
        return $this->getScore() > 0;
    }

    public function decreasesScore(): bool
    {
        return $this->getScore() < 0;
    }

    /**
     * @return list<string>
     */
    public function getRuleNames(): array
    {
        return array_map(fn (SpamTest $test): string => $test->name, $this->tests);
    }
}
