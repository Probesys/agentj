<?php

namespace App\Command;

use App\Entity\Domain;
use App\Amavis\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Repository\DomainRepository;
use App\Repository\MsgsRepository;
use App\Repository\UserRepository;
use App\Service\CryptEncryptService;
use App\Service\LogService;
use App\Service\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Amavis\DeliveryStatus;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'agentj:send-auth-mail-token',
    description: 'Send email with url token to validate email sender address',
)]
class SendAuthMailRequestCommand
{
    public function __construct(
        private DomainRepository $domainRepository,
        private MsgsRepository $messageRepository,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
        private CryptEncryptService $cryptEncryptService,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LogService $logService,
        private LocaleService $localeService,
        private LockFactory $lockFactory,
        #[Autowire(param: 'app.amavisd-release')]
        public readonly string $amavisdRelease,
        #[Autowire(param: 'app.domain_mail_authentification_sender')]
        public readonly string $defaultSenderAdress,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option('Indicates the number of days since which received emails must be analysed.')]
        int $sinceDays = 1,
    ): int {
        $lock = $this->lockFactory->createLock('msgs-send-mail-token', ttl: 1800);

        if (!$lock->acquire()) {
            $output->writeln("Can't acquire the msgs-send-mail-token lock, the command is probably already running.");
            return Command::FAILURE;
        }

        if ($sinceDays < 0) {
            $output->writeln('Days must be greater or equal to 0');
            return Command::FAILURE;
        }

        $since = new \DateTimeImmutable("-{$sinceDays} days");
        $messagesToHandle = $this->messageRepository->searchMsgsToSendAuthRequest($since);

        foreach ($messagesToHandle as $message) {
            // Don't send the authentication request to the mailing lists, but
            // set the sendCaptcha attribute so the mail isn't fetched at the
            // next call of the command.
            if ($message->getIsMlist()) {
                $message->setSendCaptcha(time());
                $this->messageRepository->save($message);
                continue;
            }

            $requiresProcessing = false;
            $recipientUsersByDomains = [];

            foreach ($message->getMsgrcpts() as $messageRecipient) {
                // If the status of a message recipient still requires to be
                // processed (null or unreleased), it means that AgentJ didn't
                // have time to consolidate its status. To put it another way:
                // we don't know yet if the mail is authorized, a spam, or
                // untreated. As the consolidation should be done in a few
                // seconds, we skip the message and we will reprocess it at the
                // next call of the command.
                if ($messageRecipient->isStatusRequiresProcessing()) {
                    $requiresProcessing = true;
                    break;
                }

                // Send the mail only for untreated emails
                if (!$messageRecipient->isUntreated()) {
                    continue;
                }

                $recipient = $messageRecipient->getRid();
                if (!$recipient) {
                    continue;
                }

                $recipientUser = $this->userRepository->findOneByMailAddress($recipient);
                if (!$recipientUser) {
                    continue;
                }

                $senderEmail = $message->getFromMimeAddress()?->getAddress();
                if (!$senderEmail) {
                    continue;
                }

                // Send only one mail per day and per recipient to the sender.
                $nbDaysLastSentMsgToUser = $this->messageRepository->getDaysSinceLastRequest(
                    $recipientUser->getEmail(),
                    $senderEmail
                );
                if ($nbDaysLastSentMsgToUser === 0) {
                    continue;
                }

                $recipientUsersByDomains[$recipient->getReverseDomain()][] = $recipientUser;
            }

            if ($requiresProcessing) {
                continue;
            }

            foreach ($recipientUsersByDomains as $domainName => $recipientUsers) {
                $domain = $this->domainRepository->findOneByDomain($domainName);

                if (!$domain) {
                    continue;
                }

                // Pick the first recipient in the list to use its name in the
                // "From" header and to use his locale when building the email.
                $recipientUser = $recipientUsers[0];

                $mailFrom = $this->getMailFrom($domain);
                $fromName = $recipientUser->getFullName();
                $locale = $this->localeService->getUserLocale($recipientUser);

                $mailBody = $this->createAuthEmailContent($domain, $message, $recipientUsers, $locale);
                $email = $this->createAuthEmail($message, $mailFrom, $fromName, $mailBody, $locale);

                if ($email === null) {
                    continue;
                }

                if ($this->sendAuthEmail($message, $email)) {
                    $this->logService->addLog(
                        'Authentification request sent',
                        $message->getMailIdAsString(),
                        $mailBody['html_body']
                    );
                    $subject = $this->getSubject($message, $locale);
                    $output->writeln(
                        date('Y-m-d H:i:s')
                        . "\t{$fromName} <{$mailFrom}>"
                        . "\t{$message->getMailIdAsString()}"
                        . "\t{$message->getSid()->getEmailClear()}"
                        . "\t{$subject}"
                    );
                }
            }

            $message->setSendCaptcha(time());
            $this->messageRepository->save($message);
        }

        $lock->release();
        return Command::SUCCESS;
    }

    /**
     * Create the message content for the authentication request
     * @param User[] $recipientUsers
     * @return string[]
     */
    private function createAuthEmailContent(Domain $domain, Msgs $msg, array $recipientUsers, string $locale): array
    {
        $token = $this->cryptEncryptService->encrypt(
            $msg->getMailIdAsString()
            . '%%%' . $msg->getSecretId()
            . '%%%' . $msg->getPartitionTag()
            . '%%%' . $domain->getId()
        );
        $url = $this->urlGenerator->generate('human_authentication', [
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        if (!empty($domain->getMailmessage())) {
            $body = $domain->getMailmessage();
        } else {
            $body = $this->translator->trans('Message.Captcha.defaultMailContent', locale: $locale);
        }

        $recipientsMailAdresses = array_map(function (User $recipientUser) {
            return $recipientUser->getEmail();
        }, $recipientUsers);

        $body = str_replace('[URL_HUMAN_AUTHENTICATION]', $url, $body);
        // URL_CAPTCHA is deprecated and has been replaced by URL_HUMAN_AUTHENTICATION.
        // We must continue to support this variable as existing templates may
        // still contain it.
        $body = str_replace('[URL_CAPTCHA]', $url, $body);
        $body = str_replace('[EMAIL_DEST]', implode(',', $recipientsMailAdresses), $body);
        $bodyTextPlain = preg_replace("/\r|\n|\t/", "", $body);
        $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $bodyTextPlain);
        $bodyTextPlain = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
            $result = mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
            return $result ?: '';
        }, $bodyTextPlain);
        $bodyTextPlain = html_entity_decode($bodyTextPlain);
        $bodyTextPlain = strip_tags($bodyTextPlain);
        $bodyTextPlain .= "\n" . $url;

        return [
            'html_body' => $body,
            'plain_body' => $bodyTextPlain,
        ];
    }

    /**
     * Set the subject of the mail send captcha from original subject
     */
    private function getSubject(Msgs $msg, string $locale): string
    {
        if ($msg->getSubject()) {
            $subject = 'Re : ' . $msg->getSubject();
        } else {
            $subject = $this->translator->trans('Message.Captcha.defaultMailSubject', locale: $locale);
        }
        return $subject;
    }

    /**
     * create Email instance
     * @param string[] $body
     */
    private function createAuthEmail(
        Msgs $message,
        string $mailFrom,
        ?string $fromName,
        array $body,
        string $locale,
    ): ?Email {
        $mailTo = $message->getSid()->getEmailClear();
        try {
            $subject = $this->getSubject($message, $locale);
            $email = new Email();

            $fromAddress = $fromName ? new Address($mailFrom, $fromName) : new Address($mailFrom);

            $email->subject($subject)
                  ->from($fromAddress)
                  ->to($mailTo)
                  ->html($body['html_body'])
                  ->text(strip_tags($body['plain_body']));

            $email->getHeaders()->addTextHeader('Auto-Submitted', 'auto-replied');
            $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
        } catch (\Exception $e) {
            //catch error and save this in msgs + change status to error
            $messageError = $e->getMessage();
            $message->setMessageError($messageError);
            $message->setStatus(MessageStatus::ERROR);
            $this->messageRepository->save($message);
            $email = null;
        }
        return $email;
    }

    /**
     * Send an authentification request email
     */
    private function sendAuthEmail(Msgs $message, Email $email): bool
    {
        try {
            $this->mailer->send($email);

            return true;
        } catch (\Exception $e) {
            $messageError = $e->getMessage();
            $message->setMessageError($messageError);
            $message->setStatus(MessageStatus::ERROR);
            $this->messageRepository->save($message);
            return false;
        }
    }

    private function getMailFrom(Domain $domain): string
    {
        if ($domain->getMailAuthenticationSender()) {
            try {
                $mailFrom = Address::create($domain->getMailAuthenticationSender())->getAddress();
            } catch (\InvalidArgumentException $e) {
                $mailFrom = $this->defaultSenderAdress;
            }
        } else {
            $mailFrom = $this->defaultSenderAdress;
        }

        return $mailFrom;
    }
}
