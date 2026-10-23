<?php

namespace App\Service;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\MessageRecipientSearchRepository;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @phpstan-type ReportContent array{
 *     subject: string, from: string, fromName: string, html: string, text: string, messageId: string
 * }
 */
class ReportMailBuilder
{
    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
        private MessageRecipientSearchRepository $messageRecipientSearchRepository,
        private MessageService $messageService,
        private UrlGeneratorInterface $urlGenerator,
        private LocaleService $localeService,
    ) {
    }

    /**
     * @return ReportContent|null
     */
    public function prepare(User $user, int $periodEnd): ?array
    {
        $messageRecipients = $this->getMessageRecipients($user, $periodEnd);
        if ($messageRecipients === []) {
            return null;
        }

        $body = $this->createEmailContent($user, $messageRecipients, $periodEnd);
        $locale = $this->localeService->getUserLocale($user);

        return [
            'subject' => $this->translator->trans('Message.Report.defaultMailSubject', locale: $locale)
                . $user->getEmail(),
            'from' => $user->getDomain()->getMailAuthenticationSender(),
            'fromName' => $this->translator->trans('Entities.Report.mailFromName', locale: $locale),
            'html' => $body,
            'text' => strip_tags(preg_replace('/<br(\s+)?\/?>/i', "\n", $body)),
            'messageId' => bin2hex(random_bytes(16)) . '@' . $user->getDomain()->getDomain(),
        ];
    }

    /**
     * @param ReportContent $content
     */
    public function createEmail(array $content, string $recipient): Email
    {
        $email = new Email();
        $email->subject($content['subject'])
            ->from(new Address($content['from'], $content['fromName']))
            ->to(new Address($recipient))
            ->html($content['html'])
            ->text($content['text']);
        $email->getHeaders()->addIdHeader('Message-ID', $content['messageId']);
        $email->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
        $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');

        return $email;
    }

    /**
     * @param MessageRecipient[] $messageRecipients
     */
    private function createEmailContent(User $user, array $messageRecipients, int $periodEnd): string
    {
        $count = fn (int $status): int => $this->messageRecipientSearchRepository->countByType(
            $user,
            $status,
            $user->getReportPeriodEnd(),
            $periodEnd,
            halfOpenPeriod: true,
        );
        $nbUntreated = $count(MessageStatus::UNTREATED);
        $nbAuthorized = $count(MessageStatus::AUTHORIZED);
        $nbBanned = $count(MessageStatus::BANNED);
        $nbDeleted = $count(MessageStatus::DELETED);
        $nbRestored = $count(MessageStatus::RESTORED);
        $nbSpammed = $count(MessageStatus::SPAMMED);
        $nbBlocked = $nbBanned + $nbSpammed + $count(MessageStatus::VIRUS);

        $domain = $user->getDomain();
        $locale = $this->localeService->getUserLocale($user);

        if ($domain && !empty($domain->getMessageAlert())) {
            $body = $domain->getMessageAlert();
        } else {
            $body = $this->translator->trans('Message.Report.defaultAlertMailContent', locale: $locale);
        }

        $tableMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'messageRecipients' => $messageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $url = $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = str_replace('[USERNAME]', $this->escape($user->getFullname()), $body);
        $body = str_replace('[LIST_MAIL_MSGS]', $tableMessages, $body);
        $body = str_replace('[NB_UNTREATED_MESSAGES]', (string) $nbUntreated, $body);
        $body = str_replace('[NB_AUTHORIZED_MESSAGES]', (string) $nbAuthorized, $body);
        $body = str_replace('[NB_SPAMMED_MESSAGES]', (string) $nbSpammed, $body);
        $body = str_replace('[NB_BLOCKED_MESSAGES]', (string) $nbBlocked, $body);
        $body = str_replace('[NB_BANNED_MESSAGES]', (string) $nbBanned, $body);
        $body = str_replace('[NB_RESTORED_MESSAGES]', (string) $nbRestored, $body);
        $body = str_replace('[NB_DELETED_MESSAGES]', (string) $nbDeleted, $body);
        $body = str_replace('[URL_MSGS]', $url, $body);

        $body = $this->replaceForeachMessages($body, $user, $messageRecipients);

        return $body;
    }

    /**
     * @param MessageRecipient[] $messageRecipients
     */
    private function replaceForeachMessages(
        string $body,
        User $user,
        array $messageRecipients
    ): string {
        // Get the part of the body before [FOREACH_MESSAGE].
        $splitBody = explode('[FOREACH_MESSAGE]', $body, 2);

        if (count($splitBody) !== 2) {
            return $body;
        }

        $bodyStart = $splitBody[0];

        // Get the part of the body after [ENDFOREACH_MESSAGE].
        $bodyRest = $splitBody[1];

        $splitBody = explode('[ENDFOREACH_MESSAGE]', $bodyRest, 2);

        if (count($splitBody) !== 2) {
            return $body;
        }

        $bodyEnd = $splitBody[1];

        // The remaining section is the one between the two previous tags, i.e.
        // the message template.
        $messageTemplate = $splitBody[0];

        $bodyMessages = '';
        foreach ($messageRecipients as $messageRecipient) {
            $message = $messageRecipient->getMessage();
            $token = $this->messageService->getReleaseToken($message, $user);

            $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
                'token' => $token,
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailId(),
                'rseqnum' => $messageRecipient->getRseqnum(),
                'new' => 1,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
                'token' => $token,
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailId(),
                'rseqnum' => $messageRecipient->getRseqnum(),
                'new' => 1,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $urlBan = $this->urlGenerator->generate('portal_message_banned', [
                'token' => $token,
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailId(),
                'rseqnum' => $messageRecipient->getRseqnum(),
                'new' => 1,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $bodyMessage = $messageTemplate;
            $bodyMessage = str_replace('[MESSAGE_SENDER]', $this->escape($message->getFromAddr()), $bodyMessage);
            $bodyMessage = str_replace('[MESSAGE_SUBJECT]', $this->escape($message->getSubject()), $bodyMessage);
            $bodyMessage = str_replace('[URL_MESSAGE_AUTHORIZE_SENDER]', $urlAuthorize, $bodyMessage);
            $bodyMessage = str_replace('[URL_MESSAGE_RESTORE]', $urlRestore, $bodyMessage);
            $bodyMessage = str_replace('[URL_MESSAGE_BAN]', $urlBan, $bodyMessage);

            $bodyMessages .= $bodyMessage;
        }

        return $bodyStart . $bodyMessages . $bodyEnd;
    }

    /**
     * @return MessageRecipient[]
     */
    private function getMessageRecipients(User $user, int $reportDate): array
    {
        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getReportPeriodEnd(),
            toDate: $reportDate,
            halfOpenPeriod: true,
        )->getResult();

        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                maxSpamLevel: $reportSpamLevel,
                fromDate: $user->getReportPeriodEnd(),
                toDate: $reportDate,
                halfOpenPeriod: true,
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        return $messageRecipients;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
