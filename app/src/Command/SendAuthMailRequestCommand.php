<?php

namespace App\Command;

use App\Entity\Domain;
use App\Amavis\MessageStatus;
use App\Entity\Msgrcpt;
use App\Entity\Msgs;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MsgsRepository;
use App\Service\CryptEncryptService;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Amavis\DeliveryStatus;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'agentj:send-auth-mail-token',
    description: 'Send email with url token to validate email sender address',
)]
class SendAuthMailRequestCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private MsgsRepository $msgsRepository,
        private TranslatorInterface $translator,
        private CryptEncryptService $cryptEncryptService,
        private MailerInterface $mailer,
        #[Autowire(param: 'domain')]
        public readonly string $agentjDomain,
        #[Autowire(param: 'scheme')]
        public readonly string $agentjDomainScheme,
        #[Autowire(param: 'app.amavisd-release')]
        public readonly string $amavisdRelease,
        #[Autowire(param: 'app.domain_mail_authentification_sender')]
        public readonly string $defaultSenderAdress,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock('msgs-send-mail-token', 1800);

        if (!$lock->acquire()) {
            $output->writeln("Can't acquire the msgs-send-mail-token lock, the command is probably already running.");
            return Command::FAILURE;
        }

        $messagesToHandle = $this->msgsRepository->searchMsgsToSendAuthRequest();
        $userRepository = $this->em->getRepository(User::class);

        foreach ($messagesToHandle as $message) {
            $recipientsByDomain = [];

            foreach ($message->getMsgrcpts() as $msgrcpt) {
                $maddr = $msgrcpt->getRid();
                if (!$maddr) {
                    continue;
                }

                if ($msgrcpt->getBl() === 'Y' || $msgrcpt->getWl() === 'Y') {
                    continue;
                }

                if ($msgrcpt->getStatus() !== null) {
                    continue;
                }

                $user = $userRepository->findOneBy([
                    'email' => $maddr->getEmailClear()
                ]);
                if ($user === null) {
                    continue;
                }

                $fromAddr = $message->getFromAddr();
                if (!$fromAddr) {
                    continue;
                }

                $messageIsPassed = $msgrcpt->getDs() === DeliveryStatus::PASS;
                $messageIsSpam = $msgrcpt->getBspamLevel() > $user->getDomain()->getLevel();

                if ($messageIsPassed || $messageIsSpam) {
                    continue;
                }

                $nbDaysLastSentMsgToUser = $this->msgsRepository->getDaysSinceLastRequest(
                    $maddr->getEmailClear(),
                    $fromAddr
                );

                if ($nbDaysLastSentMsgToUser === 0) {
                    continue;
                }

                if ($user->getBypassHumanAuth() && $msgrcpt->getStatus() === null) {
                    $this->releaseMessage($message, $msgrcpt, $user);
                    continue;
                }

                $recipientsByDomain[$msgrcpt->getRid()->getReverseDomain()][] = $msgrcpt;
            }

            foreach ($recipientsByDomain as $domainName => $messageRecipients) {
                $domainRepository = $this->em->getRepository(Domain::class);
                $domain = $domainRepository->findOneBy(['domain' => $domainName]);
                if (!$domain) {
                    continue;
                }

                $mailFrom = $this->getMailFrom($domain);
                $fromName = $this->getMailFromName($messageRecipients[0]);
                $mailBody = $this->createAuthEmailContent($domain, $message, $messageRecipients);
                $email = $this->createAuthEmail($message, $mailFrom, $fromName, $mailBody);

                if ($email === null) {
                    continue;
                }

                if ($this->sendAuthEmail($message, $email)) {
                    $logService = new LogService($this->em);
                    $logService->addLog(
                        'Authentification request sent',
                        $message->getMailIdAsString(),
                        $mailBody['html_body']
                    );
                    $subject = $this->getSubject($message);
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
            $this->em->persist($message);
            $this->em->flush();
        }

        $lock->release();
        return Command::SUCCESS;
    }

    /**
     * Create the message content for the authentication request
     * @param Msgrcpt[] $messageRecipients
     * @return string[]
     */
    private function createAuthEmailContent(Domain $domain, Msgs $msg, array $messageRecipients): array
    {
        $token = $this->cryptEncryptService->encrypt(
            $msg->getMailIdAsString()
            . '%%%' . $msg->getSecretId()
            . '%%%' . $msg->getPartitionTag()
            . '%%%' . $domain->getId()
        );
        $url = $this->agentjDomainScheme . "://" . $this->agentjDomain . "/check/" . $token;

        if (!empty($domain->getMailmessage())) {
            $body = $domain->getMailmessage();
        } else {
            $body = $this->translator->trans('Message.Captcha.defaultMailContent');
        }

        $recipientsMailAdresses = array_map(function (Msgrcpt $recipient) {
            return $recipient->getRid()->getEmailClear();
        }, $messageRecipients);

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
    private function getSubject(Msgs $msg): string
    {
        if ($msg->getSubject()) {
            $subject = 'Re : ' . $msg->getSubject();
        } else {
            $subject = $this->translator->trans('Message.Captcha.defaultMailSubject');
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
        array $body
    ): ?Email {
        $mailTo = $message->getSid()->getEmailClear();
        try {
            $subject = $this->getSubject($message);
            $email = new Email();

            $fromAddress = $fromName ? new Address($mailFrom, $fromName) : new Address($mailFrom);

            $email->subject($subject)
                  ->from($fromAddress)
                  ->to($mailTo)
                  ->html($body['html_body'])
                  ->text(strip_tags($body['plain_body']));
        } catch (\Exception $e) {
            //catch error and save this in msgs + change status to error
            $messageError = $e->getMessage();
            $message->setMessageError($messageError);
            $message->setStatus(MessageStatus::ERROR);
            $this->em->persist($message);
            $this->em->flush();
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
            $this->em->persist($message);
            $this->em->flush();
            return false;
        }
    }

    private function releaseMessage(Msgs $message, Msgrcpt $msgRcpt, User $user): void
    {
        $process = new Process([
            $this->amavisdRelease,
            stream_get_contents($message->getQuarLoc(), -1, 0),
            stream_get_contents($message->getSecretId(), -1, 0),
            $user->getEmailFromRessource(),
        ]);

        $process->run(
            function ($type, $buffer) use ($msgRcpt) {
                $msgRcpt->setAmavisOutput($buffer);
            }
        );
        $msgRcpt->setStatus(MessageStatus::AUTHORIZED);
        $this->em->persist($msgRcpt);
        $this->em->flush();
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

    private function getMailFromName(Msgrcpt $msgrcpt): ?string
    {
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $msgrcpt->getRid()->getEmailClear()]);

        return $user ? $user->getFullName() : $msgrcpt->getRid()->getEmailClear();
    }
}
