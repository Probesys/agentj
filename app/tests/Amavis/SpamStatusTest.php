<?php

namespace App\Tests\Amavis;

use App\Amavis\SpamIndicator;
use App\Amavis\SpamIndicatorMatch;
use App\Amavis\SpamStatus;
use App\Amavis\SpamTest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpamStatusTest extends TestCase
{
    public function testParseAmavisHeader(): void
    {
        $spamStatus = SpamStatus::fromHeaderValue(
            'Yes, score=-1 tag=-60 tag2=-55 kill=-50.9999999 '
            . 'tests=[ALL_TRUSTED=-1, DKIM_SIGNED=0.1, DKIM_VALID=-0.1, TRACKER_ID=0.1] '
            . 'autolearn=no autolearn_force=no',
        );

        self::assertEquals([
            new SpamTest('ALL_TRUSTED', -1.0),
            new SpamTest('DKIM_SIGNED', 0.1),
            new SpamTest('DKIM_VALID', -0.1),
            new SpamTest('TRACKER_ID', 0.1),
        ], $spamStatus->tests);
    }

    public function testParseSpamassassinHeader(): void
    {
        $spamStatus = SpamStatus::fromHeaderValue(
            'No, score=-0.1 required=5.0 tests=DKIM_SIGNED,DKIM_VALID, DKIM_VALID_AU,SPF_PASS '
            . 'autolearn=ham autolearn_force=no version=4.0.0',
        );

        self::assertEquals([
            new SpamTest('DKIM_SIGNED'),
            new SpamTest('DKIM_VALID'),
            new SpamTest('DKIM_VALID_AU'),
            new SpamTest('SPF_PASS'),
        ], $spamStatus->tests);
    }

    public function testParseHeaderWithoutTests(): void
    {
        self::assertSame([], SpamStatus::fromHeaderValue('No, score=0 tag=-60 tests=[] autolearn=no')->tests);
        self::assertSame([], SpamStatus::fromHeaderValue('No, score=0 required=5.0 tests=none')->tests);
        self::assertSame([], SpamStatus::fromHeaderValue('No, score=0')->tests);
    }

    public function testParseFoldedHeaderFromRawHeaders(): void
    {
        $rawHeaders = "Return-Path: <sender@example.com>\r\n"
            . "X-Spam-Flag: YES\r\n"
            . "X-Spam-Status: Yes, score=5.2 tag=-60 tag2=-55 kill=-50.9999999\r\n"
            . "\ttests=[BAYES_99=3.5, DKIM_SIGNED=0.1,\r\n"
            . "\tSPF_FAIL=1.6] autolearn=no autolearn_force=no\r\n"
            . "Subject: Hello\r\n"
            . "\r\n"
            . "X-Spam-Status: No, tests=[ALL_TRUSTED=-1]\r\n";

        $spamStatus = SpamStatus::fromRawHeaders($rawHeaders);

        self::assertNotNull($spamStatus);
        self::assertEquals([
            new SpamTest('BAYES_99', 3.5),
            new SpamTest('DKIM_SIGNED', 0.1),
            new SpamTest('SPF_FAIL', 1.6),
        ], $spamStatus->tests);
    }

    public function testFromRawHeadersReturnsNullWithoutHeader(): void
    {
        $rawHeaders = "Subject: Hello\r\n\r\nX-Spam-Status: No, tests=[ALL_TRUSTED=-1]\r\n";

        self::assertNull(SpamStatus::fromRawHeaders($rawHeaders));
    }

    public function testGetIndicatorsGroupsAndSortsTests(): void
    {
        $spamStatus = SpamStatus::fromHeaderValue(
            'Yes, score=4.1 tests=[ALL_TRUSTED=-1, DKIM_SIGNED=0.1, DKIM_VALID=-0.1, DKIM_VALID_AU=-0.1, '
            . 'HTML_MESSAGE=0.001, KAM_SOMETHING_NEW=0.4, TRACKER_ID=0.1, URIBL_BLACK=1.7, URIBL_DBL_SPAM=2.5, '
            . 'URIBL_BLOCKED=0.001]',
        );

        $indicators = array_map(
            fn (SpamIndicatorMatch $match): array => [
                $match->indicator,
                $match->getFormattedScore(),
                $match->getRuleNames(),
            ],
            $spamStatus->getIndicators(),
        );

        self::assertSame([
            [SpamIndicator::LinkBlocklisted, '+4.2', ['URIBL_BLACK', 'URIBL_DBL_SPAM']],
            [SpamIndicator::TrustedRelay, '-1', ['ALL_TRUSTED']],
            [SpamIndicator::DkimValid, '-0.2', ['DKIM_VALID', 'DKIM_VALID_AU']],
            [SpamIndicator::Tracking, '+0.1', ['TRACKER_ID']],
            [SpamIndicator::Other, '+0.4', ['KAM_SOMETHING_NEW']],
        ], $indicators);
    }

    public function testFormattedScore(): void
    {
        $match = fn (?float ...$scores): string => (new SpamIndicatorMatch(
            SpamIndicator::Other,
            array_values(array_map(fn (?float $score): SpamTest => new SpamTest('RULE', $score), $scores)),
        ))->getFormattedScore();

        self::assertSame('+1.5', $match(1.5));
        self::assertSame('-0.001', $match(-0.001));
        self::assertSame('+3', $match(1, 2));
        self::assertSame('0', $match(0.1, -0.1));
        self::assertSame('', $match(null));
    }

    #[DataProvider('rulesProvider')]
    public function testRuleIndicator(string $rule, ?SpamIndicator $expectedIndicator): void
    {
        self::assertSame($expectedIndicator, SpamIndicator::fromRule($rule));
    }

    /**
     * @return iterable<array{string, ?SpamIndicator}>
     */
    public static function rulesProvider(): iterable
    {
        yield ['DKIM_SIGNED', null];
        yield ['HTML_MESSAGE', null];
        yield ['URIBL_BLOCKED', null];
        yield ['RCVD_IN_ZEN_BLOCKED_OPENDNS', null];
        yield ['T_SCC_BODY_TEXT_LINE', null];
        yield ['SPF_PASS', SpamIndicator::SpfPass];
        yield ['SPF_HELO_PASS', SpamIndicator::SpfPass];
        yield ['SPF_FAIL', SpamIndicator::SpfFail];
        yield ['SPF_SOFTFAIL', SpamIndicator::SpfSoftFail];
        yield ['SPF_HELO_NONE', SpamIndicator::SpfNone];
        yield ['SPF_PERMERROR', SpamIndicator::SpfError];
        yield ['DKIM_VALID_AU', SpamIndicator::DkimValid];
        yield ['DKIM_INVALID', SpamIndicator::DkimInvalid];
        yield ['DKIM_ADSP_NXDOMAIN', SpamIndicator::DkimInvalid];
        yield ['DMARC_PASS', SpamIndicator::DmarcPass];
        yield ['DMARC_REJECT', SpamIndicator::DmarcFail];
        yield ['KAM_DMARC_QUARANTINE', SpamIndicator::DmarcFail];
        yield ['DMARC_MISSING', SpamIndicator::DmarcMissing];
        yield ['ALL_TRUSTED', SpamIndicator::TrustedRelay];
        yield ['USER_IN_WELCOMELIST', SpamIndicator::SenderAllowlisted];
        yield ['USER_IN_DEF_DKIM_WL', SpamIndicator::SenderAllowlisted];
        yield ['USER_IN_BLOCKLIST', SpamIndicator::SenderBlocklisted];
        yield ['TXREP', SpamIndicator::SenderHistory];
        yield ['RCVD_IN_DNSWL_HI', SpamIndicator::ServerGoodReputation];
        yield ['RCVD_IN_MSPIKE_H3', SpamIndicator::ServerGoodReputation];
        yield ['RCVD_IN_MSPIKE_L5', SpamIndicator::ServerBlocklisted];
        yield ['RCVD_IN_BLOCKLISTDE', SpamIndicator::ServerBlocklisted];
        yield ['RCVD_IN_VALIDITY_RPBL', SpamIndicator::ServerBlocklisted];
        yield ['RDNS_NONE', SpamIndicator::ServerMisconfigured];
        yield ['HELO_DYNAMIC_IPADDR', SpamIndicator::ServerMisconfigured];
        yield ['BAYES_00', SpamIndicator::LearnedHam];
        yield ['BAYES_50', SpamIndicator::LearnedNeutral];
        yield ['BAYES_999', SpamIndicator::LearnedSpam];
        yield ['RAZOR2_CHECK', SpamIndicator::KnownSpam];
        yield ['DCC_CHECK', SpamIndicator::KnownSpam];
        yield ['GTUBE', SpamIndicator::KnownSpam];
        yield ['URI_PHISH', SpamIndicator::Phishing];
        yield ['SEM_FRESH30', SpamIndicator::NewDomain];
        yield ['FROM_FMBLA_NEWDOM', SpamIndicator::NewDomain];
        yield ['URIBL_ABUSE_SURBL', SpamIndicator::LinkBlocklisted];
        yield ['SEM_URI', SpamIndicator::LinkBlocklisted];
        yield ['NORMAL_HTTP_TO_IP', SpamIndicator::LinkSuspicious];
        yield ['HTTPS_HTTP_MISMATCH', SpamIndicator::LinkSuspicious];
        yield ['TRACKER_ID', SpamIndicator::Tracking];
        yield ['OLEMACRO_DOWNLOAD_EXE', SpamIndicator::DangerousAttachment];
        yield ['T_OBFU_DOC_ATTACH', SpamIndicator::DangerousAttachment];
        yield ['FREEMAIL_FORGED_REPLYTO', SpamIndicator::Spoofing];
        yield ['FORGED_GMAIL_RCVD', SpamIndicator::Spoofing];
        yield ['TO_EQ_FM_DOM_SPF_FAIL', SpamIndicator::Spoofing];
        yield ['FREEMAIL_FROM', SpamIndicator::Freemail];
        yield ['MAILING_LIST_MULTI', SpamIndicator::MailingList];
        yield ['MIME_HTML_ONLY', SpamIndicator::SuspiciousFormatting];
        yield ['HTML_IMAGE_RATIO_02', SpamIndicator::SuspiciousFormatting];
        yield ['HTML_FONT_LOW_CONTRAST', SpamIndicator::SuspiciousFormatting];
        yield ['MISSING_MID', SpamIndicator::MalformedMessage];
        yield ['MIME_QP_LONG_LINE', SpamIndicator::MalformedMessage];
        yield ['EMPTY_MESSAGE', SpamIndicator::MalformedMessage];
        yield ['SUBJ_ALL_CAPS', SpamIndicator::SpammyContent];
        yield ['LOTS_OF_MONEY', SpamIndicator::SpammyContent];
        yield ['ADVANCE_FEE_2_NEW_MONEY', SpamIndicator::SpammyContent];
        yield ['KAM_SOMETHING_NEW', SpamIndicator::Other];
    }
}
