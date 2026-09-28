<?php

namespace App\Tests\Entity;

use App\Amavis\SpamIndicator;
use App\Entity\Message;
use App\Entity\Quarantine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testGetSpamIndicatorsWithoutQuarantine(): void
    {
        self::assertSame([], (new Message())->getSpamIndicators());
    }

    public function testGetSpamIndicatorsFromFoldedHeader(): void
    {
        $message = $this->messageFromChunks(
            "Return-Path: <sender@example.com>\r\n"
            . "X-Spam-Flag: YES\r\n"
            . "X-Spam-Status: Yes, score=5.2 tag=-60 tag2=-55 kill=-50.9999999\r\n"
            . "\ttests=[BAYES_99=3.5, DKIM_SIGNED=0.1,\r\n"
            . "\tSPF_FAIL=1.6] autolearn=no autolearn_force=no\r\n"
            . "Subject: Hello\r\n"
            . "\r\n"
            . "X-Spam-Status: No, tests=[ALL_TRUSTED=-1]\r\n",
        );

        self::assertSame([
            ['indicator' => SpamIndicator::LearnedSpam, 'score' => 3.5, 'rules' => ['BAYES_99']],
            ['indicator' => SpamIndicator::SpfFail, 'score' => 1.6, 'rules' => ['SPF_FAIL']],
            ['indicator' => SpamIndicator::DkimSigned, 'score' => 0.1, 'rules' => ['DKIM_SIGNED']],
        ], $message->getSpamIndicators());
    }

    public function testGetSpamIndicatorsFromHeaderSplitAcrossChunks(): void
    {
        $message = $this->messageFromChunks(
            "Subject: Hello\r\nX-Spam-Status: Yes, tests=[SPF_",
            "FAIL=3]\r\n\r\nBody",
        );

        self::assertSame([
            ['indicator' => SpamIndicator::SpfFail, 'score' => 3.0, 'rules' => ['SPF_FAIL']],
        ], $message->getSpamIndicators());
    }

    public function testGetSpamIndicatorsFromAmavisHeader(): void
    {
        $message = $this->messageFromChunks(
            "Subject: Hello\r\n"
            . 'X-Spam-Status: Yes, score=-1 tag=-60 tag2=-55 kill=-50.9999999 '
            . 'tests=[ALL_TRUSTED=-1, DKIM_SIGNED=0.1, DKIM_VALID=-0.1, TRACKER_ID=0.1] '
            . "autolearn=no autolearn_force=no\r\n\r\nBody",
        );

        self::assertSame([
            ['indicator' => SpamIndicator::TrustedRelay, 'score' => -1.0, 'rules' => ['ALL_TRUSTED']],
            ['indicator' => SpamIndicator::DkimSigned, 'score' => 0.1, 'rules' => ['DKIM_SIGNED']],
            ['indicator' => SpamIndicator::DkimValid, 'score' => -0.1, 'rules' => ['DKIM_VALID']],
            ['indicator' => SpamIndicator::Tracking, 'score' => 0.1, 'rules' => ['TRACKER_ID']],
        ], $message->getSpamIndicators());
    }

    public function testGetSpamIndicatorsFromNativeSpamassassinHeader(): void
    {
        $message = $this->messageFromChunks(
            "Subject: Hello\r\n"
            . 'X-Spam-Status: No, score=-0.1 required=5.0 tests=DKIM_SIGNED,DKIM_VALID, DKIM_VALID_AU,SPF_PASS '
            . "autolearn=ham autolearn_force=no version=4.0.0\r\n\r\nBody",
        );

        self::assertSame([
            ['indicator' => SpamIndicator::DkimSigned, 'score' => null, 'rules' => ['DKIM_SIGNED']],
            ['indicator' => SpamIndicator::DkimValid, 'score' => null, 'rules' => ['DKIM_VALID', 'DKIM_VALID_AU']],
            ['indicator' => SpamIndicator::SpfPass, 'score' => null, 'rules' => ['SPF_PASS']],
        ], $message->getSpamIndicators());
    }

    #[DataProvider('headersWithoutTestsProvider')]
    public function testGetSpamIndicatorsWithoutTests(string $header): void
    {
        $message = $this->messageFromChunks("Subject: Hello\r\nX-Spam-Status: {$header}\r\n\r\nBody");

        self::assertSame([], $message->getSpamIndicators());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function headersWithoutTestsProvider(): iterable
    {
        yield 'empty tests' => ['No, score=0 tag=-60 tests=[] autolearn=no'];
        yield 'none tests' => ['No, score=0 required=5.0 tests=none'];
        yield 'missing tests' => ['No, score=0'];
        yield 'invalid score' => ['No, tests=[SPF_FAIL=invalid]'];
    }

    public function testGetSpamIndicatorsParsesSignedDecimalScoresAndNormalizesRuleNames(): void
    {
        $message = $this->messageFromChunks(
            "Subject: Hello\r\nX-Spam-Status: No, tests=[spf_fail=+1.5, DKIM_VALID=-.001]\r\n\r\nBody",
        );

        self::assertSame([
            ['indicator' => SpamIndicator::SpfFail, 'score' => 1.5, 'rules' => ['SPF_FAIL']],
            ['indicator' => SpamIndicator::DkimValid, 'score' => -0.001, 'rules' => ['DKIM_VALID']],
        ], $message->getSpamIndicators());
    }

    #[DataProvider('malformedMailHeadersProvider')]
    public function testGetSpamIndicatorsDoesNotFailOnMalformedMail(string $headers): void
    {
        $message = $this->messageFromChunks(
            $headers . "\r\nX-Spam-Status: Yes, tests=[SPF_FAIL=3]\r\n\r\nBody",
        );

        self::assertSame([], $message->getSpamIndicators());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedMailHeadersProvider(): iterable
    {
        yield 'invalid date' => ['Date: definitely-not-a-date'];
        yield 'invalid month' => ['Date: Thu, 05 XXX 2026 10:00:00 +0000'];
        yield 'duplicate dates' => ["Date: Thu, 05 Nov 2026 10:00:00 +0000\r\nDate: not-a-date"];
        yield 'three dates' => [implode("\r\n", array_fill(0, 3, 'Date: Thu, 05 Nov 2026 10:00:00 +0000'))];
        yield 'multipart without boundary' => ['Content-Type: multipart/mixed'];
    }

    private function messageFromChunks(string ...$contents): Message
    {
        $message = new Message();
        foreach ($contents as $content) {
            $chunk = $this->createStub(Quarantine::class);
            $chunk->method('getMailText')->willReturn($content);
            $message->getQuarantineChunks()->add($chunk);
        }

        return $message;
    }
}
