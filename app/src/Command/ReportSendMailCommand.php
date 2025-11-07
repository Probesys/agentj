<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Msgrcpt;
use App\Amavis\MessageStatus;
use App\Repository\MsgrcptSearchRepository;
use App\Service\LocaleService;
use App\Service\MessageService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsCommand(
    name: 'agentj:report-send-mail',
    description: 'Send report email',
)]
class ReportSendMailCommand extends Command
{
    public function __construct(
        private Environment $twig,
        private ManagerRegistry $doctrine,
        private TranslatorInterface $translator,
        private MailerInterface $mailer,
        #[Autowire(param: 'app.domain_mail_authentification_sender')]
        private string $defaultMailFrom,
        private MsgrcptSearchRepository $msgrcptSearchRepository,
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

            $untreatedMessageRecipients = $this->msgrcptSearchRepository->getSearchQuery(
                $user,
                fromDate: $user->getDateLastReport()
            )->getResult();

            if (count($untreatedMessageRecipients) === 0) {
                continue;
            }

            $body = $this->createEmailContent($user, $untreatedMessageRecipients);

            $domain = $user->getDomain();
            $from = $domain->getMailAuthenticationSender();
            if (!$from) {
                $from = $this->defaultMailFrom;
            }

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
                //catch error and save this in msgs + change status to error
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
     * @param Msgrcpt[] $untreatedMessageRecipients
     */
    private function createEmailContent(User $user, array $untreatedMessageRecipients): string
    {
        $untreatedMessageRecipients = array_slice($untreatedMessageRecipients, 0, 10);
        $nbUntreated = $this->msgrcptSearchRepository->countByType($user, MessageStatus::UNTREATED);
        $nbAuthorized = $this->msgrcptSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
        $nbBanned = $this->msgrcptSearchRepository->countByType($user, MessageStatus::BANNED);
        $nbDeleted = $this->msgrcptSearchRepository->countByType($user, MessageStatus::DELETED);
        $nbRestored = $this->msgrcptSearchRepository->countByType($user, MessageStatus::RESTORED);
        $nbSpammed = $this->msgrcptSearchRepository->countByType($user, MessageStatus::SPAMMED);

        $domain = $user->getDomain();
        $locale = $this->localeService->getUserLocale($user);

        if ($domain && !empty($domain->getMessageAlert())) {
            $body = $domain->getMessageAlert();
        } else {
            $body = $this->translator->trans('Message.Report.defaultAlertMailContent', locale: $locale);
        }

        $tableMessages = $this->twig->render('report/table_mail_msgs.html.twig', [
            'untreatedMessageRecipients' => $untreatedMessageRecipients,
            'user' => $user,
            'locale' => $locale,
        ]);

        $url = $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = str_replace('[USERNAME]', $user->getFullname(), $body);
        $body = str_replace('[LIST_MAIL_MSGS]', $tableMessages, $body);
        $body = str_replace('[NB_UNTREATED_MESSAGES]', (string) $nbUntreated, $body);
        $body = str_replace('[NB_AUTHORIZED_MESSAGES]', (string) $nbAuthorized, $body);
        $body = str_replace('[NB_SPAMMED_MESSAGES]', (string) $nbSpammed, $body);
        $body = str_replace('[NB_BANNED_MESSAGES]', (string) $nbBanned, $body);
        $body = str_replace('[NB_RESTORED_MESSAGES]', (string) $nbRestored, $body);
        $body = str_replace('[NB_DELETED_MESSAGES]', (string) $nbDeleted, $body);
        $body = str_replace('[URL_MSGS]', $url, $body);

        $body = $this->replaceForeachMessages($body, $user, $untreatedMessageRecipients);

        return $body;
    }

    /**
     * @param Msgrcpt[] $untreatedMessageRecipients
     */
    private function replaceForeachMessages(
        string $body,
        User $user,
        array $untreatedMessageRecipients
    ): string {
        // Get the part of the body before [FOREACH_MESSAGE].
        $splittedBody = explode('[FOREACH_MESSAGE]', $body, 2);

        if (count($splittedBody) !== 2) {
            return $body;
        }

        $bodyStart = $splittedBody[0];

        // Get the part of the body after [ENDFOREACH_MESSAGE].
        $bodyRest = $splittedBody[1];

        $splittedBody = explode('[ENDFOREACH_MESSAGE]', $bodyRest, 2);

        if (count($splittedBody) !== 2) {
            return $body;
        }

        $bodyEnd = $splittedBody[1];

        // The remaining section is the one between the two previous tags, i.e.
        // the message template.
        $messageTemplate = $splittedBody[0];

        $bodyMessages = '';
        foreach ($untreatedMessageRecipients as $messageRecipient) {
            $message = $messageRecipient->getMsgs();
            $token = $this->messageService->getReleaseToken($message, $user);

            $urlAuthorize = $this->urlGenerator->generate('portal_message_authorized', [
                'token' => $token,
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailIdAsString(),
                'recipientId' => $messageRecipient->getRid()->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $urlRestore = $this->urlGenerator->generate('portal_message_restore', [
                'token' => $token,
                'partitionTag' => $messageRecipient->getPartitionTag(),
                'mailId' => $messageRecipient->getMailIdAsString(),
                'recipientId' => $messageRecipient->getRid()->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $bodyMessage = $messageTemplate;
            $bodyMessage = str_replace('[MESSAGE_SENDER]', $message->getFromAddr(), $bodyMessage);
            $bodyMessage = str_replace('[MESSAGE_SUBJECT]', $message->getSubject(), $bodyMessage);
            $bodyMessage = str_replace('[URL_MESSAGE_AUTHORIZE_SENDER]', $urlAuthorize, $bodyMessage);
            $bodyMessage = str_replace('[URL_MESSAGE_RESTORE]', $urlRestore, $bodyMessage);

            $bodyMessages .= $bodyMessage;
        }

        return $bodyStart . $bodyMessages . $bodyEnd;
    }
}
