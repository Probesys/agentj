<?php

namespace App\Command;

use App\Entity\User;
use App\Amavis\MessageStatus;
use App\Repository\MsgrcptSearchRepository;
use App\Service;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[AsCommand(
    name: 'agentj:report-send-mail',
    description: 'Send report email',
)]
class ReportSendMailCommand extends Command {
    public function __construct(
        private Environment $twig,
        private ManagerRegistry $doctrine,
        private TranslatorInterface $translator,
        private MailerInterface $mailer,
        private Service\CryptEncryptService $cryptEncryptService,
        #[Autowire(param: 'app.domain_mail_authentification_sender')]
        private string $defaultMailFrom,
        private ParameterBagInterface $params,
        private MsgrcptSearchRepository $msgrcptSearchRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Send report email ');
    }

    protected function execute(InputInterface $input, OutputInterface $output):int {
        $io = new SymfonyStyle($input, $output);

        $em = $this->doctrine->getManager();

        $domain = $this->params->get('domain');
        $scheme = $this->params->get('scheme');

        $url = $scheme . "://" . $domain;
        $i = 0;

        // Get users to send report
        $allUsers = $em->getRepository(User::class)->activeUsers();

        foreach ($allUsers as $userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if ($user && $user->getReport()) {
                /**
                 * Récupérer les liste des messages non traités depuis le dernier envoie du rapport
                 * N'envoyer le rapport que si ce nombre est > 0
                 */

                $untreatedMsgs = $this->msgrcptSearchRepository->getSearchQuery(
                    $user, fromDate: $user->getDateLastReport()
                )->getResult();
                $totalUnread = count($untreatedMsgs);

                if ($totalUnread == 0) {
                    continue;
                }

                $untreatedMsgs = array_slice($untreatedMsgs, 0, 10);
                $nbAuthorized = $this->msgrcptSearchRepository->countByType($user, MessageStatus::AUTHORIZED);
                $nbBanned = $this->msgrcptSearchRepository->countByType($user, MessageStatus::BANNED);
                $nbDeleted = $this->msgrcptSearchRepository->countByType($user, MessageStatus::DELETED);
                $nbRestored = $this->msgrcptSearchRepository->countByType($user, MessageStatus::RESTORED);
                $nbSpammed = $this->msgrcptSearchRepository->countByType($user, MessageStatus::SPAMMED);
                $domain = $user->getDomain();

                if ($domain && !empty($domain->getMessageAlert())) {
                    $body = $domain->getMessageAlert();
                } else {
                    $body = $this->translator->trans('Message.Report.defaultAlertMailContent');
                }

                $token = $this->cryptEncryptService->encrypt((string) $user->getId(), lifetime: 48 * 3600);

                $tableMsgs = $this->twig->render('report/table_mail_msgs.html.twig', [
                    'untreatedMsgs' => $untreatedMsgs,
                    'url' => $url,
                    'token' => $token,
                ]);

                $body = str_replace('[USERNAME]', $user->getFullname(), $body);
                $body = str_replace('[LIST_MAIL_MSGS]', $tableMsgs, $body);
                $body = str_replace('[NB_AUTHORIZED_MESSAGES]', (string) $nbAuthorized, $body);
                $body = str_replace('[NB_SPAMMED_MESSAGES]', (string) $nbSpammed, $body);
                $body = str_replace('[NB_BANNED_MESSAGES]', (string) $nbBanned, $body);
                $body = str_replace('[NB_RESTORED_MESSAGES]', (string) $nbRestored, $body);
                $body = str_replace('[NB_DELETED_MESSAGES]', (string) $nbDeleted, $body);
                $body = str_replace('[URL_MSGS]', $url, $body);

                $fromName = $this->translator->trans('Entities.Report.mailFromName');
                $mailTo = stream_get_contents($user->getEmail(), -1, 0);
                $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $body);
                $bodyTextPlain = strip_tags($bodyTextPlain);

                $message = new Email();
                $message->subject($this->translator->trans('Message.Report.defaultMailSubject') . $mailTo )
                        ->from(new Address($this->defaultMailFrom, $fromName))
                        ->to($mailTo)
                        ->html($body)->text(strip_tags($bodyTextPlain));


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
                    $io->note(sprintf('Error  %s : [%s]', $user->getEmailFromRessource(), $messageError));
                }
            }
        }

        if ($i == 0) {
            $io->success(' No message send.');
        } else {
            $io->success($i . ' messages sent.');
        }
        return Command::SUCCESS;
    }

}
