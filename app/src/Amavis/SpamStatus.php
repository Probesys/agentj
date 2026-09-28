<?php

namespace App\Amavis;

/**
 * The SpamAssassin results of a message, as written by Amavis in the
 * X-Spam-Status header, e.g.:
 *
 *     X-Spam-Status: Yes, score=7.3 tag=-60 tag2=-55 kill=-50.9999999
 *         tests=[ALL_TRUSTED=-1, DKIM_SIGNED=0.1, DKIM_VALID=-0.1,
 *         TRACKER_ID=0.1] autolearn=no autolearn_force=no
 *
 * The SpamAssassin native format (tests=ALL_TRUSTED,DKIM_SIGNED) is also
 * supported, even if it does not include the scores.
 */
final readonly class SpamStatus
{
    public const HEADER_NAME = 'X-Spam-Status';

    /**
     * @param list<SpamTest> $tests
     */
    public function __construct(
        public array $tests,
    ) {
    }

    /**
     * Build a SpamStatus from the raw headers of an email, or return null if
     * the X-Spam-Status header is missing.
     */
    public static function fromRawHeaders(string $rawHeaders): ?self
    {
        // Only keep the headers, in case the body is part of the given string.
        $rawHeaders = preg_split('/\r?\n\r?\n/', $rawHeaders, 2)[0] ?? '';

        // Unfold the headers (RFC 5322 section 2.2.3).
        $rawHeaders = preg_replace('/\r?\n[ \t]+/', ' ', $rawHeaders) ?? $rawHeaders;

        $pattern = '/^' . preg_quote(self::HEADER_NAME, '/') . ':(.*)$/mi';
        if (!preg_match($pattern, $rawHeaders, $matches)) {
            return null;
        }

        return self::fromHeaderValue($matches[1]);
    }

    public static function fromHeaderValue(string $headerValue): self
    {
        return new self(self::parseTests($headerValue));
    }

    /**
     * @return list<SpamTest>
     */
    private static function parseTests(string $headerValue): array
    {
        $rule = '[A-Za-z0-9_]+';
        $score = '[-+]?(?:\d+\.?\d*|\.\d+)';
        $test = "{$rule}(?:={$score})?";

        // Either tests=[A=1, B=-0.1] (Amavis) or tests=A,B (SpamAssassin).
        $testsPattern = "/(?:^|\s)tests=(?:\[([^\]]*)\]|({$test}(?:\s*,\s*{$test})*))/i";
        if (!preg_match($testsPattern, $headerValue, $matches)) {
            return [];
        }

        $rawTests = $matches[1] !== '' ? $matches[1] : ($matches[2] ?? '');

        $tests = [];
        foreach (preg_split('/\s*,\s*/', trim($rawTests), flags: PREG_SPLIT_NO_EMPTY) ?: [] as $rawTest) {
            if (!preg_match("/^({$rule})(?:=({$score}))?$/", $rawTest, $testMatches)) {
                continue;
            }

            $name = strtoupper($testMatches[1]);
            if ($name === 'NONE') {
                continue;
            }

            $tests[] = new SpamTest(
                $name,
                isset($testMatches[2]) ? (float) $testMatches[2] : null,
            );
        }

        return $tests;
    }

    /**
     * Return the indicators matched by the tests, the most significant first.
     *
     * Tests that are purely informative are ignored.
     *
     * @return list<SpamIndicatorMatch>
     */
    public function getIndicators(): array
    {
        $testsByIndicator = [];

        foreach ($this->tests as $test) {
            $indicator = $test->getIndicator();
            if ($indicator === null) {
                continue;
            }

            $testsByIndicator[$indicator->value][] = $test;
        }

        $indicators = [];
        foreach ($testsByIndicator as $indicatorValue => $tests) {
            $indicators[] = new SpamIndicatorMatch(SpamIndicator::from($indicatorValue), $tests);
        }

        usort($indicators, function (SpamIndicatorMatch $a, SpamIndicatorMatch $b): int {
            // The generic indicator is always displayed last.
            $aIsOther = $a->indicator === SpamIndicator::Other;
            $bIsOther = $b->indicator === SpamIndicator::Other;
            if ($aIsOther !== $bIsOther) {
                return $aIsOther <=> $bIsOther;
            }

            // Then, sort by decreasing impact on the spam score.
            return abs($b->getScore() ?? 0.0) <=> abs($a->getScore() ?? 0.0);
        });

        return $indicators;
    }
}
