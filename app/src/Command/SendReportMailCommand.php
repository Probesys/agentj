<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Repository\MessageRecipientSearchRepository;
use App\Service\LocaleService;
use App\Service\MessageService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[AsCommand(
    name: 'agentj:send-report-mail|agentj:report-send-mail',
    description: 'Send report email',
)]
class SendReportMailCommand extends Command
{
    public function __construct(
        private Environment $twig,
        private ManagerRegistry $doctrine,
        private TranslatorInterface $translator,
        private MailerInterface $mailer,
        private MessageRecipientSearchRepository $messageRecipientSearchRepository,
        private MessageService $messageService,
        private UrlGeneratorInterface $urlGenerator,
        private LocaleService $localeService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Send report email ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $em = $this->doctrine->getManager();

        $i = 0;

        // Get users to send report
        $allUsers = $em->getRepository(User::class)->activeUsers();

        foreach ($allUsers as $userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if (!$user || !$user->getReport()) {
                continue;
            }

            $messageRecipients = $this->getMessageRecipients($user);

            if (count($messageRecipients['all']) === 0) {
                continue;
            }

            $body = $this->createEmailContent(
                $user,
                $messageRecipients['all'],
                $messageRecipients['list'],
                $messageRecipients['spam'],
                $messageRecipients['standard'],
            );

            $domain = $user->getDomain();
            $from = $domain->getMailAuthenticationSender();

            $locale = $this->localeService->getUserLocale($user);
            $fromName = $this->translator->trans('Entities.Report.mailFromName', locale: $locale);

            $fromAddress = new Address($from, $fromName);

            $mailTo = $user->getEmail();

            if ($mailTo === null) {
                continue;
            }

            $toAddress = new Address($mailTo);

            $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $body);
            $bodyTextPlain = strip_tags($bodyTextPlain);

            $subject = $this->translator->trans('Message.Report.defaultMailSubject', locale: $locale) . $mailTo;

            $message = new Email();
            $message->subject($subject)
                    ->from($fromAddress)
                    ->to($toAddress)
                    ->html($body)->text(strip_tags($bodyTextPlain));

            $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
            $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');

            try {
                $this->mailer->send($message);
                $user->setDateLastReport(time());
                $em->persist($user);
                $em->flush();

                $output->writeln(date('Y-m-d H:i:s') . "\tReport sent to " . $mailTo);
                $i++;
            } catch (\Exception $e) {
                //catch error and save this in message + change status to error
                $messageError = $e->getMessage();
                $io->note(sprintf('Error  %s : [%s]', $user->getEmail(), $messageError));
            }
        }

        if ($i == 0) {
            $io->success(' No message send.');
        } else {
            $io->success($i . ' messages sent.');
        }
        return Command::SUCCESS;
    }

    /**
     * @param MessageRecipient[] $messageRecipients
     * @param MessageRecipient[] $listMessageRecipients
     * @param MessageRecipient[] $spamMessageRecipients
     * @param MessageRecipient[] $standardMessageRecipients
     */
    private function createEmailContent(
        User $user,
        array $messageRecipients,
        array $listMessageRecipients,
        array $spamMessageRecipients,
        array $standardMessageRecipients,
    ): string {
        $nbUntreated = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::UNTREATED);
        $nbAuthorized = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
        $nbBanned = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::BANNED);
        $nbDeleted = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::DELETED);
        $nbRestored = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::RESTORED);
        $nbSpammed = $this->messageRecipientSearchRepository->countByType($user, MessageStatus::SPAMMED);

        $domain = $user->getDomain();
        $locale = $this->localeService->getUserLocale($user);

        if ($domain && !empty($domain->getMessageAlert())) {
            $body = $domain->getMessageAlert();
        } else {
            $body = $this->translator->trans('Message.Report.defaultAlertMailContent', locale: $locale);
        }

        $tableMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'type' => 'all',
            'messageRecipients' => $messageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $tableListMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'type' => 'list',
            'messageRecipients' => $listMessageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $tableSpamMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'type' => 'spam',
            'messageRecipients' => $spamMessageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $tableStandardMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'type' => 'standard',
            'messageRecipients' => $standardMessageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $url = $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = str_replace('[USERNAME]', $this->escape($user->getFullname()), $body);
        $body = str_replace('[LIST_MAIL_MSGS]', $tableMessages, $body);
        $body = str_replace('[LIST_MESSAGES_LIST]', $tableListMessages, $body);
        $body = str_replace('[LIST_MESSAGES_SPAM]', $tableSpamMessages, $body);
        $body = str_replace('[LIST_MESSAGES_STANDARD]', $tableStandardMessages, $body);
        $body = str_replace('[NB_UNTREATED_MESSAGES]', (string) $nbUntreated, $body);
        $body = str_replace('[NB_AUTHORIZED_MESSAGES]', (string) $nbAuthorized, $body);
        $body = str_replace('[NB_SPAMMED_MESSAGES]', (string) $nbSpammed, $body);
        $body = str_replace('[NB_BANNED_MESSAGES]', (string) $nbBanned, $body);
        $body = str_replace('[NB_RESTORED_MESSAGES]', (string) $nbRestored, $body);
        $body = str_replace('[NB_DELETED_MESSAGES]', (string) $nbDeleted, $body);
        $body = str_replace('[URL_MSGS]', $url, $body);

        $body = $this->replaceForeachMessages(
            $body,
            $user,
            $messageRecipients,
            $standardMessageRecipients,
            $listMessageRecipients,
            $spamMessageRecipients,
        );

        return $body;
    }

    /**
     * @param MessageRecipient[] $messageRecipients
     * @param MessageRecipient[] $standardMessageRecipients
     * @param MessageRecipient[] $listMessageRecipients
     * @param MessageRecipient[] $spamMessageRecipients
     */
    private function replaceForeachMessages(
        string $body,
        User $user,
        array $messageRecipients,
        array $standardMessageRecipients,
        array $listMessageRecipients,
        array $spamMessageRecipients,
    ): string {
        $types = [
            'MESSAGE',
            'MESSAGE_STANDARD',
            'MESSAGE_LIST',
            'MESSAGE_SPAM',
        ];

        foreach ($types as $type) {
            // Get the part of the body before [FOREACH_$type].
            $splitBody = explode("[FOREACH_$type]", $body, 2);

            if (count($splitBody) !== 2) {
                continue;
            }

            $bodyStart = $splitBody[0];

            // Get the part of the body after [ENDFOREACH_$type].
            $bodyRest = $splitBody[1];

            $splitBody = explode("[ENDFOREACH_$type]", $bodyRest, 2);

            if (count($splitBody) !== 2) {
                continue;
            }

            $bodyEnd = $splitBody[1];

            // The remaining section is the one between the two previous tags, i.e.
            // the message template.
            $messageTemplate = $splitBody[0];

            $messageRecipientByType = match ($type) {
                'MESSAGE' => $messageRecipients,
                'MESSAGE_STANDARD' => $standardMessageRecipients,
                'MESSAGE_LIST' => $listMessageRecipients,
                'MESSAGE_SPAM' => $spamMessageRecipients,
            };

            if (count($messageRecipientByType) === 0) {
                $body = $bodyStart . $bodyEnd;
                continue;
            }

            $bodyMessages = '';
            foreach ($messageRecipientByType as $messageRecipient) {
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

            $body = $bodyStart . $bodyMessages . $bodyEnd;
        }

        return $body;
    }


    /**
     * @return array{
     *     all: MessageRecipient[],
     *     standard: MessageRecipient[],
     *     list: MessageRecipient[],
     *     spam: MessageRecipient[],
     * }
     *  */
    private function getMessageRecipients(User $user): array
    {
        $messageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
            $user,
            messageStatus: MessageStatus::UNTREATED,
            fromDate: $user->getDateLastReport(),
        )->getResult();

        $domain = $user->getDomain();
        $reportSpamLevel = $domain->getReportSpamLevel();

        if ($reportSpamLevel > 0) {
            $spamMessageRecipients = $this->messageRecipientSearchRepository->getSearchQuery(
                $user,
                messageStatus: MessageStatus::SPAMMED,
                maxSpamLevel: $reportSpamLevel,
                fromDate: $user->getDateLastReport(),
            )->getResult();

            $messageRecipients = array_merge($messageRecipients, $spamMessageRecipients);
        }

        $standard = array_filter(
            $messageRecipients,
            fn (MessageRecipient $messageRecipient) =>
                $messageRecipient->getStatus() === MessageStatus::UNTREATED &&
                !$messageRecipient->getMessage()->getIsMlist(),
        );
        $list = array_filter(
            $messageRecipients,
            fn (MessageRecipient $messageRecipient) =>
                $messageRecipient->getStatus() === MessageStatus::UNTREATED &&
                $messageRecipient->getMessage()->getIsMlist(),
        );
        $spam = array_filter(
            $messageRecipients,
            fn (MessageRecipient $messageRecipient) =>
                $messageRecipient->getStatus() === MessageStatus::SPAMMED,
        );

        return [
            'all' => $messageRecipients,
            'standard' => $standard,
            'list' => $list,
            'spam' => $spam,
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
