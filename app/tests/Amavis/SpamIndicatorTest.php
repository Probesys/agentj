<?php

namespace App\Tests\Amavis;

use App\Amavis\SpamIndicator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpamIndicatorTest extends TestCase
{
    public function testFromTestsGroupsAndSortsIndicators(): void
    {
        $indicators = SpamIndicator::fromTests([
            'ALL_TRUSTED' => -1.0,
            'DKIM_SIGNED' => 0.1,
            'DKIM_VALID' => -0.1,
            'DKIM_VALID_AU' => -0.1,
            'HTML_MESSAGE' => 0.001,
            'KAM_SOMETHING_NEW' => 0.4,
            'TRACKER_ID' => 0.1,
            'URIBL_BLACK' => 1.7,
            'URIBL_DBL_SPAM' => 2.5,
            'URIBL_BLOCKED' => 0.001,
        ]);

        self::assertSame([
            [
                'indicator' => SpamIndicator::LinkBlocklisted,
                'score' => 4.2,
                'rules' => ['URIBL_BLACK', 'URIBL_DBL_SPAM'],
            ],
            ['indicator' => SpamIndicator::TrustedRelay, 'score' => -1.0, 'rules' => ['ALL_TRUSTED']],
            ['indicator' => SpamIndicator::DkimValid, 'score' => -0.2, 'rules' => ['DKIM_VALID', 'DKIM_VALID_AU']],
            ['indicator' => SpamIndicator::DkimSigned, 'score' => 0.1, 'rules' => ['DKIM_SIGNED']],
            ['indicator' => SpamIndicator::Tracking, 'score' => 0.1, 'rules' => ['TRACKER_ID']],
            ['indicator' => SpamIndicator::Other, 'score' => 0.402, 'rules' => [
                'HTML_MESSAGE', 'KAM_SOMETHING_NEW', 'URIBL_BLOCKED',
            ]],
        ], $indicators);
    }

    public function testFromTestsWithoutIndicators(): void
    {
        self::assertSame([], SpamIndicator::fromTests([]));
    }

    public function testFromTestsRoundsScoresAfterSumming(): void
    {
        $indicators = SpamIndicator::fromTests(['DKIM_VALID' => -0.0006, 'DKIM_VALID_AU' => -0.0006]);

        self::assertSame(-0.001, $indicators[0]['score']);
    }

    public function testFromTestsPreservesZeroScore(): void
    {
        $indicators = SpamIndicator::fromTests(['DKIM_VALID' => 0.1, 'DKIM_VALID_AU' => -0.1]);

        self::assertSame(0.0, $indicators[0]['score']);
    }

    public function testFromTestsPreservesUnknownScores(): void
    {
        self::assertSame([
            ['indicator' => SpamIndicator::DkimValid, 'score' => null, 'rules' => ['DKIM_VALID', 'DKIM_VALID_AU']],
        ], SpamIndicator::fromTests(['DKIM_VALID' => null, 'DKIM_VALID_AU' => null]));
    }

    public function testFromTestsSumsKnownScoresWhenSomeAreMissing(): void
    {
        self::assertSame([
            ['indicator' => SpamIndicator::DkimValid, 'score' => -0.1, 'rules' => ['DKIM_VALID', 'DKIM_VALID_AU']],
        ], SpamIndicator::fromTests(['DKIM_VALID' => null, 'DKIM_VALID_AU' => -0.1]));
    }

    #[DataProvider('rulesProvider')]
    public function testRuleIndicator(string $rule, ?SpamIndicator $expectedIndicator): void
    {
        self::assertSame($expectedIndicator, SpamIndicator::fromRule($rule));
    }

    /**
     * @return iterable<string, array{string, ?SpamIndicator}>
     */
    public static function rulesProvider(): iterable
    {
        yield 'HTML_MESSAGE maps to Other' => ['HTML_MESSAGE', SpamIndicator::Other];
        yield 'URIBL_BLOCKED maps to Other' => ['URIBL_BLOCKED', SpamIndicator::Other];
        yield 'URIBL_DBL_ERROR maps to Other' => ['URIBL_DBL_ERROR', SpamIndicator::Other];
        yield 'RCVD_IN_ZEN_BLOCKED_OPENDNS maps to Other' => ['RCVD_IN_ZEN_BLOCKED_OPENDNS', SpamIndicator::Other];
        yield 'T_SCC_BODY_TEXT_LINE maps to Other' => ['T_SCC_BODY_TEXT_LINE', SpamIndicator::Other];
        yield 'SPF_PASS maps to SpfPass' => ['SPF_PASS', SpamIndicator::SpfPass];
        yield 'SPF_HELO_PASS maps to SpfPass' => ['SPF_HELO_PASS', SpamIndicator::SpfPass];
        yield 'SPF_FAIL maps to SpfFail' => ['SPF_FAIL', SpamIndicator::SpfFail];
        yield 'SPF_SOFTFAIL maps to SpfSoftFail' => ['SPF_SOFTFAIL', SpamIndicator::SpfSoftFail];
        yield 'SPF_HELO_NONE maps to SpfNone' => ['SPF_HELO_NONE', SpamIndicator::SpfNone];
        yield 'SPF_NEUTRAL maps to SpfNeutral' => ['SPF_NEUTRAL', SpamIndicator::SpfNeutral];
        yield 'SPF_HELO_NEUTRAL maps to SpfNeutral' => ['SPF_HELO_NEUTRAL', SpamIndicator::SpfNeutral];
        yield 'SPF_PERMERROR maps to SpfError' => ['SPF_PERMERROR', SpamIndicator::SpfError];
        yield 'DKIM_SIGNED maps to DkimSigned' => ['DKIM_SIGNED', SpamIndicator::DkimSigned];
        yield 'DKIM_VALID maps to DkimValid' => ['DKIM_VALID', SpamIndicator::DkimValid];
        yield 'DKIM_VALID_AU maps to DkimValid' => ['DKIM_VALID_AU', SpamIndicator::DkimValid];
        yield 'DKIM_VALID_EF maps to DkimValid' => ['DKIM_VALID_EF', SpamIndicator::DkimValid];
        yield 'DKIM_INVALID maps to DkimInvalid' => ['DKIM_INVALID', SpamIndicator::DkimInvalid];
        yield 'DKIM_ADSP_NXDOMAIN maps to DkimInvalid' => ['DKIM_ADSP_NXDOMAIN', SpamIndicator::DkimInvalid];
        yield 'DMARC_PASS maps to DmarcPass' => ['DMARC_PASS', SpamIndicator::DmarcPass];
        yield 'DMARC_REJECT maps to DmarcFail' => ['DMARC_REJECT', SpamIndicator::DmarcFail];
        yield 'KAM_DMARC_QUARANTINE maps to DmarcFail' => ['KAM_DMARC_QUARANTINE', SpamIndicator::DmarcFail];
        yield 'DMARC_MISSING maps to DmarcMissing' => ['DMARC_MISSING', SpamIndicator::DmarcMissing];
        yield 'ALL_TRUSTED maps to TrustedRelay' => ['ALL_TRUSTED', SpamIndicator::TrustedRelay];
        yield 'USER_IN_WELCOMELIST maps to SenderAllowlisted' => [
            'USER_IN_WELCOMELIST',
            SpamIndicator::SenderAllowlisted,
        ];
        yield 'USER_IN_DEF_DKIM_WL maps to SenderAllowlisted' => [
            'USER_IN_DEF_DKIM_WL',
            SpamIndicator::SenderAllowlisted,
        ];
        yield 'USER_IN_BLOCKLIST maps to SenderBlocklisted' => ['USER_IN_BLOCKLIST', SpamIndicator::SenderBlocklisted];
        yield 'TXREP maps to SenderHistory' => ['TXREP', SpamIndicator::SenderHistory];
        yield 'RCVD_IN_IADB_VOUCHED maps to ServerGoodReputation' => [
            'RCVD_IN_IADB_VOUCHED',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_IADB_GOODMAIL maps to ServerGoodReputation' => [
            'RCVD_IN_IADB_GOODMAIL',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_IADB_DOPTIN maps to ServerGoodReputation' => [
            'RCVD_IN_IADB_DOPTIN',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_IADB_ML_DOPTIN maps to ServerGoodReputation' => [
            'RCVD_IN_IADB_ML_DOPTIN',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_IADB_OPTIN maps to ServerGoodReputation' => [
            'RCVD_IN_IADB_OPTIN',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_IADB_NOCONTROL maps to Other' => ['RCVD_IN_IADB_NOCONTROL', SpamIndicator::Other];
        yield 'RCVD_IN_IADB_OPTOUTONLY maps to Other' => ['RCVD_IN_IADB_OPTOUTONLY', SpamIndicator::Other];
        yield 'RCVD_IN_IADB_LISTED maps to Other' => ['RCVD_IN_IADB_LISTED', SpamIndicator::Other];
        yield 'RCVD_IN_IADB_RDNS maps to Other' => ['RCVD_IN_IADB_RDNS', SpamIndicator::Other];
        yield 'RCVD_IN_IADB_UNKNOWN maps to Other' => ['RCVD_IN_IADB_UNKNOWN', SpamIndicator::Other];
        yield 'RCVD_IN_IADB_VOUCHED_UNKNOWN maps to Other' => ['RCVD_IN_IADB_VOUCHED_UNKNOWN', SpamIndicator::Other];
        yield 'RCVD_IN_DNSWL_NONE maps to ServerNeutralReputation' => [
            'RCVD_IN_DNSWL_NONE',
            SpamIndicator::ServerNeutralReputation,
        ];
        yield 'RCVD_IN_MSPIKE_H2 maps to ServerNeutralReputation' => [
            'RCVD_IN_MSPIKE_H2',
            SpamIndicator::ServerNeutralReputation,
        ];
        yield 'RCVD_IN_DNSWL_LOW maps to ServerGoodReputation' => [
            'RCVD_IN_DNSWL_LOW',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_DNSWL_MED maps to ServerGoodReputation' => [
            'RCVD_IN_DNSWL_MED',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_DNSWL_HI maps to ServerGoodReputation' => [
            'RCVD_IN_DNSWL_HI',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_MSPIKE_H3 maps to ServerGoodReputation' => [
            'RCVD_IN_MSPIKE_H3',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_MSPIKE_H4 maps to ServerGoodReputation' => [
            'RCVD_IN_MSPIKE_H4',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_MSPIKE_H5 maps to ServerGoodReputation' => [
            'RCVD_IN_MSPIKE_H5',
            SpamIndicator::ServerGoodReputation,
        ];
        yield 'RCVD_IN_MSPIKE_L5 maps to ServerBlocklisted' => ['RCVD_IN_MSPIKE_L5', SpamIndicator::ServerBlocklisted];
        yield 'RCVD_IN_BLOCKLISTDE maps to ServerBlocklisted' => [
            'RCVD_IN_BLOCKLISTDE',
            SpamIndicator::ServerBlocklisted,
        ];
        yield 'RCVD_IN_VALIDITY_RPBL maps to ServerBlocklisted' => [
            'RCVD_IN_VALIDITY_RPBL',
            SpamIndicator::ServerBlocklisted,
        ];
        yield 'RDNS_NONE maps to ServerMisconfigured' => ['RDNS_NONE', SpamIndicator::ServerMisconfigured];
        yield 'RCVD_HELO_IP_MISMATCH maps to ServerMisconfigured' => [
            'RCVD_HELO_IP_MISMATCH',
            SpamIndicator::ServerMisconfigured,
        ];
        yield 'HELO_DYNAMIC_IPADDR maps to ServerMisconfigured' => [
            'HELO_DYNAMIC_IPADDR',
            SpamIndicator::ServerMisconfigured,
        ];
        yield 'BAYES_00 maps to LearnedHam' => ['BAYES_00', SpamIndicator::LearnedHam];
        yield 'BAYES_50 maps to LearnedNeutral' => ['BAYES_50', SpamIndicator::LearnedNeutral];
        yield 'BAYES_999 maps to LearnedSpam' => ['BAYES_999', SpamIndicator::LearnedSpam];
        yield 'RAZOR2_CHECK maps to KnownSpam' => ['RAZOR2_CHECK', SpamIndicator::KnownSpam];
        yield 'DCC_CHECK maps to KnownSpam' => ['DCC_CHECK', SpamIndicator::KnownSpam];
        yield 'DCC_REPUT_00_12 maps to Other' => ['DCC_REPUT_00_12', SpamIndicator::Other];
        yield 'DCC_REPUT_13_19 maps to Other' => ['DCC_REPUT_13_19', SpamIndicator::Other];
        yield 'DCC_REPUT_70_89 maps to Other' => ['DCC_REPUT_70_89', SpamIndicator::Other];
        yield 'DCC_REPUT_90_94 maps to Other' => ['DCC_REPUT_90_94', SpamIndicator::Other];
        yield 'DCC_REPUT_95_98 maps to Other' => ['DCC_REPUT_95_98', SpamIndicator::Other];
        yield 'DCC_REPUT_99_100 maps to Other' => ['DCC_REPUT_99_100', SpamIndicator::Other];
        yield 'GTUBE maps to KnownSpam' => ['GTUBE', SpamIndicator::KnownSpam];
        yield 'URI_PHISH maps to Phishing' => ['URI_PHISH', SpamIndicator::Phishing];
        yield 'SEM_FRESH30 maps to NewDomain' => ['SEM_FRESH30', SpamIndicator::NewDomain];
        yield 'FROM_FMBLA_NEWDOM maps to NewDomain' => ['FROM_FMBLA_NEWDOM', SpamIndicator::NewDomain];
        yield 'URIBL_RHS_DOB maps to NewDomain' => ['URIBL_RHS_DOB', SpamIndicator::NewDomain];
        yield 'URIBL_ABUSE_SURBL maps to LinkBlocklisted' => ['URIBL_ABUSE_SURBL', SpamIndicator::LinkBlocklisted];
        yield 'SEM_URI maps to LinkBlocklisted' => ['SEM_URI', SpamIndicator::LinkBlocklisted];
        yield 'NORMAL_HTTP_TO_IP maps to LinkSuspicious' => ['NORMAL_HTTP_TO_IP', SpamIndicator::LinkSuspicious];
        yield 'HTTPS_HTTP_MISMATCH maps to LinkSuspicious' => ['HTTPS_HTTP_MISMATCH', SpamIndicator::LinkSuspicious];
        yield 'SPOOF_COM2COM maps to LinkSuspicious' => ['SPOOF_COM2COM', SpamIndicator::LinkSuspicious];
        yield 'SPOOF_COM2OTH maps to LinkSuspicious' => ['SPOOF_COM2OTH', SpamIndicator::LinkSuspicious];
        yield 'SPOOF_NET2COM maps to LinkSuspicious' => ['SPOOF_NET2COM', SpamIndicator::LinkSuspicious];
        yield 'T_PDS_SHORT_SPOOFED_URL maps to LinkSuspicious' => [
            'T_PDS_SHORT_SPOOFED_URL',
            SpamIndicator::LinkSuspicious,
        ];
        yield 'T_PDS_NO_FULL_NAME_SPOOFED_URL maps to LinkSuspicious' => [
            'T_PDS_NO_FULL_NAME_SPOOFED_URL',
            SpamIndicator::LinkSuspicious,
        ];
        yield 'TRACKER_ID maps to Tracking' => ['TRACKER_ID', SpamIndicator::Tracking];
        yield 'OLEMACRO_DOWNLOAD_EXE maps to DangerousAttachment' => [
            'OLEMACRO_DOWNLOAD_EXE',
            SpamIndicator::DangerousAttachment,
        ];
        yield 'T_OBFU_DOC_ATTACH maps to DangerousAttachment' => [
            'T_OBFU_DOC_ATTACH',
            SpamIndicator::DangerousAttachment,
        ];
        yield 'FREEMAIL_FORGED_REPLYTO maps to Spoofing' => ['FREEMAIL_FORGED_REPLYTO', SpamIndicator::Spoofing];
        yield 'FORGED_GMAIL_RCVD maps to Spoofing' => ['FORGED_GMAIL_RCVD', SpamIndicator::Spoofing];
        yield 'TO_EQ_FM_DOM_SPF_FAIL maps to Spoofing' => ['TO_EQ_FM_DOM_SPF_FAIL', SpamIndicator::Spoofing];
        yield 'FREEMAIL_FROM maps to Freemail' => ['FREEMAIL_FROM', SpamIndicator::Freemail];
        yield 'MAILING_LIST_MULTI maps to MailingList' => ['MAILING_LIST_MULTI', SpamIndicator::MailingList];
        yield 'MIME_HTML_ONLY maps to SuspiciousFormatting' => ['MIME_HTML_ONLY', SpamIndicator::SuspiciousFormatting];
        yield 'HTML_IMAGE_RATIO_02 maps to SuspiciousFormatting' => [
            'HTML_IMAGE_RATIO_02',
            SpamIndicator::SuspiciousFormatting,
        ];
        yield 'HTML_FONT_LOW_CONTRAST maps to SuspiciousFormatting' => [
            'HTML_FONT_LOW_CONTRAST',
            SpamIndicator::SuspiciousFormatting,
        ];
        yield 'MISSING_MID maps to MalformedMessage' => ['MISSING_MID', SpamIndicator::MalformedMessage];
        yield 'MIME_QP_LONG_LINE maps to MalformedMessage' => ['MIME_QP_LONG_LINE', SpamIndicator::MalformedMessage];
        yield 'MIME_CHARSET_FARAWAY maps to Other' => ['MIME_CHARSET_FARAWAY', SpamIndicator::Other];
        yield 'EMPTY_MESSAGE maps to MalformedMessage' => ['EMPTY_MESSAGE', SpamIndicator::MalformedMessage];
        yield 'SUBJ_ALL_CAPS maps to SpammyContent' => ['SUBJ_ALL_CAPS', SpamIndicator::SpammyContent];
        yield 'LOTS_OF_MONEY maps to SpammyContent' => ['LOTS_OF_MONEY', SpamIndicator::SpammyContent];
        yield 'ADVANCE_FEE_2_NEW_MONEY maps to SpammyContent' => [
            'ADVANCE_FEE_2_NEW_MONEY',
            SpamIndicator::SpammyContent,
        ];
        yield 'KAM_SOMETHING_NEW maps to Other' => ['KAM_SOMETHING_NEW', SpamIndicator::Other];
    }
}
