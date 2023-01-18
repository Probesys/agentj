<?php

namespace App\Command;

use App\Entity\Msgs;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ReportSendMailCommand extends Command {

    protected static $defaultName = 'agentj:report-send-mail';
    private $twig;
    private $doctrine;
    private $translator;

    public function __construct(Environment $twig, ManagerRegistry $doctrine, TranslatorInterface $translator) {
        // Inject it in the constructor and update the value on the class
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('Send report email ');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
//        $translator = $this->getContainer()->get('translator');

        $io = new SymfonyStyle($input, $output);

        $em = $this->doctrine->getManager();

        $container = $this->getApplication()->getKernel()->getContainer();
        $domain = $container->getParameter('domain');
        $scheme = $container->getParameter('scheme');
//        $failedRecipients = [];

        $url = $scheme . "://" . $domain;
        $transport_server = $this->getApplication()->getKernel()->getContainer()->getParameter('app.smtp-transport_report');
        $i = 0;

        // Get users to send report
        $allUsers = $em->getRepository(User::class)->activeUsers();

        foreach ($allUsers as $userId) {
            /* @var $user User */
            $user = $em->getRepository(User::class)->find($userId);
            if ($user && $user->getReport()) {

                /**
                 * Récupérer les liste des messages non traités depuis le dernier envoie du rapport
                 * N'envoyer le rapport que si ce nombre est > 0
                 */
                $alias = $em->getRepository(User::class)->findBy(['originalUser' => $user]);
                $untreatedMsgs = $em->getRepository(Msgs::class)->search($user, null, $alias, null, null, $user->getDateLastReport());
                if (count($untreatedMsgs) == 0) {
                    continue;
                }
                $nbAuthorized = $em->getRepository(Msgs::class)->countByType($user, 2, $alias);
                $nbBanned = $em->getRepository(Msgs::class)->countByType($user, 1, $alias);
                $nbDeleted = $em->getRepository(Msgs::class)->countByType($user, 3, $alias);
                $nbRestored = $em->getRepository(Msgs::class)->countByType($user, 5, $alias);
                $nbSpammed = $em->getRepository(Msgs::class)->countByType($user, 6, $alias);
                $domain = $user->getDomain();

                if ($domain && !empty($domain->getMessageAlert())) {
                    $body = $domain->getMessageAlert();
                } else {
                    $body = $this->translator->trans('Message.Report.defaultAlertMailContent');
                }

                $url = $this->getApplication()->getKernel()->getContainer()->getParameter('scheme');
                $url .= "://" . $this->getApplication()->getKernel()->getContainer()->getParameter('domain');

                $tableMsgs = $this->twig->render('report/table_mail_msgs.html.twig', ['untreatedMsgs' => $untreatedMsgs, 'url' => $url]);
                //        $tableMsgs = $this->renderView('report/table_mail_msgs.html.twig', ['untreatedMsgs' => $untreatedMsgs]);

                $body = str_replace('[USERNAME]', $user->getFullname(), $body);
                $body = str_replace('[LIST_MAIL_MSGS]', $tableMsgs, $body);
                $body = str_replace('[NB_AUTHORIZED_MESSAGES]', $nbAuthorized, $body);
                $body = str_replace('[NB_SPAMMED_MESSAGES]', $nbSpammed, $body);
                $body = str_replace('[NB_BANNED_MESSAGES]', $nbBanned, $body);
                $body = str_replace('[NB_RESTORED_MESSAGES]', $nbRestored, $body);
                $body = str_replace('[NB_DELETED_MESSAGES]', $nbDeleted, $body);
                $body = str_replace('[URL_MSGS]', $url, $body);
                //$mailFrom = 'no-reply@' . $domain->getDomain();

                $mailFrom = $this->getApplication()->getKernel()->getContainer()->getParameter('app.domain_mail_authentification_sender');
                $fromName = $this->translator->trans('Entities.Report.mailFromName');
                $mailTo = stream_get_contents($user->getEmail(), -1, 0);
                $bodyTextPlain = preg_replace('/<br(\s+)?\/?>/i', "\n", $body);
                $bodyTextPlain = strip_tags($bodyTextPlain);
                
                $message = new Email();
                $message->subject($this->translator->trans('Message.Report.defaultMailSubject') . $mailTo )
                        ->from(new Address($mailFrom, $fromName))
                        ->to($mailTo)
                        ->html($body)->text(strip_tags($bodyTextPlain));


                try {
                    $transport = Transport::fromDsn('smtp://' . $transport_server . ':25');
                    $mailer = new Mailer($transport);

                    $mailer->send($message);
                    $user->setDateLastReport(time());
                    $em->persist($user);
                    $em->flush();

                    $output->writeln(date('Y-m-d H:i:s') . "\tReport sent to " . $mailTo);
                    $i++;
                } catch (\Exception $e) {
                    //catch error and save this in msgs + change status to error
                    $messageError = $e->getMessage();
                    $io->note(sprintf('Error  %s : [%s]', $user->getEmail(), $messageError));
                    return Command::FAILURE;
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
