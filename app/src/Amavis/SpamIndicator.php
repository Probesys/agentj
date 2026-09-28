<?php

namespace App\Amavis;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * User-friendly categories of the SpamAssassin tests.
 *
 * @see https://spamassassin.apache.org/old/tests_3_3_x.html
 */
enum SpamIndicator: string
{
    // Sender authentication
    case SpfPass = 'spf_pass';
    case SpfFail = 'spf_fail';
    case SpfSoftFail = 'spf_softfail';
    case SpfNone = 'spf_none';
    case SpfError = 'spf_error';
    case DkimValid = 'dkim_valid';
    case DkimInvalid = 'dkim_invalid';
    case DmarcPass = 'dmarc_pass';
    case DmarcFail = 'dmarc_fail';
    case DmarcMissing = 'dmarc_missing';
    case Spoofing = 'spoofing';

    // Sender and infrastructure reputation
    case TrustedRelay = 'trusted_relay';
    case SenderAllowlisted = 'sender_allowlisted';
    case SenderBlocklisted = 'sender_blocklisted';
    case SenderHistory = 'sender_history';
    case ServerGoodReputation = 'server_good_reputation';
    case ServerBlocklisted = 'server_blocklisted';
    case ServerMisconfigured = 'server_misconfigured';
    case NewDomain = 'new_domain';
    case Freemail = 'freemail';
    case MailingList = 'mailing_list';

    // Content
    case Phishing = 'phishing';
    case LinkBlocklisted = 'link_blocklisted';
    case LinkSuspicious = 'link_suspicious';
    case Tracking = 'tracking';
    case DangerousAttachment = 'dangerous_attachment';
    case KnownSpam = 'known_spam';
    case LearnedHam = 'learned_ham';
    case LearnedNeutral = 'learned_neutral';
    case LearnedSpam = 'learned_spam';
    case SpammyContent = 'spammy_content';
    case SuspiciousFormatting = 'suspicious_formatting';
    case MalformedMessage = 'malformed_message';

    // Any rule not listed below
    case Other = 'other';

    /**
     * Patterns of SpamAssassin rule names, and their indicator. The first
     * matching pattern wins, so specific patterns must come first. A null
     * indicator means that the rule is purely informative and must be ignored.
     *
     * @var array<string, ?self>
     */
    private const RULES = [
        // Informative rules, or rules about the scanner itself (e.g. DNS
        // queries refused by the blocklist providers).
        '/^(DKIM_SIGNED|ARC_SIGNED|ARC_VALID|HTML_MESSAGE|NO_RELAYS|NO_RECEIVED|SCC_BODY_TEXT_LINE)$/' => null,
        '/^KAM_DMARC_STATUS$/' => null,
        '/_BLOCKED(_|$)/' => null,

        '/^SPF_(HELO_)?PASS$/' => self::SpfPass,
        '/^SPF_(HELO_)?FAIL$/' => self::SpfFail,
        '/^SPF_(HELO_)?SOFTFAIL$/' => self::SpfSoftFail,
        '/^SPF_(HELO_)?(NONE|NEUTRAL)$/' => self::SpfNone,
        '/^SPF_/' => self::SpfError,
        '/^DKIM_VALID/' => self::DkimValid,
        '/^DKIM_(INVALID|ADSP_)/' => self::DkimInvalid,
        '/^DMARC_PASS$/' => self::DmarcPass,
        '/^DMARC_MISSING$/' => self::DmarcMissing,
        '/^(KAM_)?DMARC_/' => self::DmarcFail,

        '/^ALL_TRUSTED$/' => self::TrustedRelay,
        '/^(AWL|TXREP|AUTO_WHITELIST|AUTO_WELCOMELIST)$/' => self::SenderHistory,
        '/^(RCVD_IN_(DNSWL|IADB|MSPIKE_(H\d|WL)|VALIDITY_(CERTIFIED|SAFE))|DKIMWL_WL_)/' => self::ServerGoodReputation,
        '/^(RCVD_IN_|RBL_|BL_SPAMCOP)/' => self::ServerBlocklisted,
        '/(WHITELIST|WELCOMELIST|^USER_IN_.*_WL$)/' => self::SenderAllowlisted,
        '/(BLACKLIST|BLOCKLIST)/' => self::SenderBlocklisted,

        '/^BAYES_(00|05|20)$/' => self::LearnedHam,
        '/^BAYES_(40|50)$/' => self::LearnedNeutral,
        '/^BAYES_/' => self::LearnedSpam,
        '/^(RAZOR2|PYZOR|DCC_|DIGEST_|GTUBE$)/' => self::KnownSpam,

        '/PHISH/' => self::Phishing,
        '/(FRESH|NEWDOM)/' => self::NewDomain,
        '/^(URIBL_|SURBL_|SEM_URI|DBL_)|_URIBL|_SURBL/' => self::LinkBlocklisted,
        '/(TRACKER|TRACKING|WEB_BUG)/' => self::Tracking,
        '/^(HTML_|MIME_HTML_|MPART_ALT_DIFF)|IMAGE|_IMG|FONT/' => self::SuspiciousFormatting,
        '/(ATTACH|MACRO|_EXE|EXE_|_ZIP|ZIP_|_RAR|RAR_|_JAR|_LNK|_PDF|PDF_)/' => self::DangerousAttachment,
        '/(^URI_|_URI_|URISHRT|SHORTENER|HTTP_TO_IP|HTTP_ADDR|HTTP_MISMATCH|IP_MISMATCH|WEIRD_PORT|^URL_|_URL_)/'
            => self::LinkSuspicious,

        '/(FORGED|SPOOF|FAKE|^TO_EQ_FM|REPLYTO|REPLY_TO|FROM_2_EMAILS|HEADER_FROM_DIFFERENT)/' => self::Spoofing,
        '/^FREEMAIL_/' => self::Freemail,
        '/^(MAILING_LIST|LIST_)/' => self::MailingList,
        '/^(RDNS_|HELO_|CK_HELO|FSL_HELO|DYN_|NO_DNS_FOR_FROM|DNS_FROM_)|DYNAMIC/' => self::ServerMisconfigured,

        '/^(MIME_|MISSING_|INVALID_|DATE_IN_|HEADER_|HDRS_|MSGID|CTE_|TO_NO_BRKTS|TO_MALFORMED|UNPARSEABLE)'
            . '|EMPTY|EXCESS_BASE64/' => self::MalformedMessage,
        '/(SUBJ|UPPERCASE|MONEY|ADVANCE_FEE|LOTTO|LOTTERY|PILL|DRUG|VIAGRA|CASH|PRICE|FREE|DEAR_|FILL_THIS_FORM'
            . '|GAPPY|OBFU|FUZZY_|BODY|SPAMMY|SCAM|INHERIT|BITCOIN|CRYPTO|INVESTMENT|GUARANTEE|PHARM|STOCK|LOAN'
            . '|MILLION|WINNER|CLICK|PORN|SEX|DATING|UNSUB)/' => self::SpammyContent,
    ];

    /**
     * Return the indicator of a SpamAssassin rule, or null if the rule is
     * purely informative.
     */
    public static function fromRule(string $rule): ?self
    {
        $rule = strtoupper($rule);

        // "T_" is the prefix of the rules that are being tested by SpamAssassin.
        if (str_starts_with($rule, 'T_')) {
            $rule = substr($rule, 2);
        }

        foreach (self::RULES as $pattern => $indicator) {
            if (preg_match($pattern, $rule)) {
                return $indicator;
            }
        }

        return self::Other;
    }

    public function getLabel(): TranslatableMessage
    {
        return match ($this) {
            self::SpfPass => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spf_pass'),
            self::SpfFail => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spf_fail'),
            self::SpfSoftFail => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spf_softfail'),
            self::SpfNone => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spf_none'),
            self::SpfError => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spf_error'),
            self::DkimValid => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.dkim_valid'),
            self::DkimInvalid => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.dkim_invalid'),
            self::DmarcPass => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.dmarc_pass'),
            self::DmarcFail => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.dmarc_fail'),
            self::DmarcMissing => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.dmarc_missing'),
            self::Spoofing => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spoofing'),
            self::TrustedRelay => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.trusted_relay'),
            self::SenderAllowlisted => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.sender_allowlisted'),
            self::SenderBlocklisted => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.sender_blocklisted'),
            self::SenderHistory => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.sender_history'),
            self::ServerGoodReputation => new TranslatableMessage(
                'Entities.Msgrcpt.spamIndicators.server_good_reputation',
            ),
            self::ServerBlocklisted => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.server_blocklisted'),
            self::ServerMisconfigured => new TranslatableMessage(
                'Entities.Msgrcpt.spamIndicators.server_misconfigured',
            ),
            self::NewDomain => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.new_domain'),
            self::Freemail => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.freemail'),
            self::MailingList => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.mailing_list'),
            self::Phishing => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.phishing'),
            self::LinkBlocklisted => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.link_blocklisted'),
            self::LinkSuspicious => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.link_suspicious'),
            self::Tracking => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.tracking'),
            self::DangerousAttachment => new TranslatableMessage(
                'Entities.Msgrcpt.spamIndicators.dangerous_attachment',
            ),
            self::KnownSpam => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.known_spam'),
            self::LearnedHam => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.learned_ham'),
            self::LearnedNeutral => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.learned_neutral'),
            self::LearnedSpam => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.learned_spam'),
            self::SpammyContent => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.spammy_content'),
            self::SuspiciousFormatting => new TranslatableMessage(
                'Entities.Msgrcpt.spamIndicators.suspicious_formatting',
            ),
            self::MalformedMessage => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.malformed_message'),
            self::Other => new TranslatableMessage('Entities.Msgrcpt.spamIndicators.other'),
        };
    }
}
