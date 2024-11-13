<?php
namespace App\MessageHandler;

use App\Message\CreateAlertMessage;
use App\Entity\OutMsg;
use App\Entity\SqlLimitReport;
use App\Entity\Alert;
use App\Entity\User;
use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CreateAlertMessageHandler
{
    private EntityManagerInterface $entityManager;
    private ConsoleOutput $output;
    private Mailer $mailer;
    private string $defaultMailFrom;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->output = new ConsoleOutput();
        $this->defaultMailFrom = $params->get('app.domain_mail_authentification_sender');
    }

    public function __invoke(CreateAlertMessage $message)
    {
        $type = $message->getType();

        if ($type === 'out_msg') {
            $this->handleOutMsg($message);
        } elseif ($type === 'sql_limit_report') {
            $this->handleSqlLimitReport($message);
        } else {
            $this->output->writeln('Error: Unknown message type: ' . $type);
        }
    }

    private function handleOutMsg(CreateAlertMessage $message)
    {
        $mailId = $message->getId();
        $this->output->writeln('Handler invoked for OutMsg ID: ' . $mailId);

        // Convert mailId back to binary
        $binaryMailId = hex2bin($mailId);
        if ($binaryMailId === false) {
            $this->output->writeln('Error: Failed to convert mailId to binary');
            return;
        }

        // Ensure the field name matches the database column
        $outMsg = $this->entityManager->getRepository(OutMsg::class)->findOneBy(['mail_id' => $binaryMailId]);
        $this->output->writeln('OutMsg fetched: ' . ($outMsg ? 'Yes' : 'No'));

        if ($outMsg) {
            // Refresh the entity to ensure it has the latest data
            $this->entityManager->refresh($outMsg);

            $this->output->writeln('OutMsg content: ' . $outMsg->getContent());

            if ($outMsg->getContent() === 'V') {
                $target = $message->getTarget();

                // Fetch the user who sent the email based on from_addr
                $fromAddr = $outMsg->getFromAddr();
                $senderUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $fromAddr]);

                $timezone = new \DateTimeZone('Europe/Paris');

                if ($target === 'admin') {
                    // Fetch users with ROLE_SUPER_ADMIN or ROLE_ADMIN
                    $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
                        ->where('u.roles LIKE :superAdminRole')
                        ->orWhere('u.roles LIKE :adminRole')
                        ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%')
                        ->setParameter('adminRole', '%"ROLE_ADMIN"%')
                        ->getQuery()
                        ->getResult();

                    foreach ($users as $user) {
                        $alert = new Alert();
                        $alert->setAlertType('out_msgs');
                        $alert->setRefId($mailId);
                        $alert->setDate(new \DateTime('now', $timezone));
                        $alert->setSubject('Mail sortant contenant un virus bloqué');
                        $alert->setIsRead(false);
                        $alert->setUser($user);
                        $alert->setRefUser($fromAddr);

                        $this->entityManager->persist($alert);

                        // Send email to admin/superadmin
                        $emailAddress = $user->getEmailFromRessource();
                        if ($emailAddress !== null) {
                            $smtpServer = $user->getDomain()->getSrvSmtp();
                            $smtpPort = $user->getDomain()->getSmtpPort();
                            $transport = Transport::fromDsn('smtp://' . $smtpServer . ':' . $smtpPort);
                            $mailer = new Mailer($transport);

                            $mailFrom = $user->getDomain()->getMailAuthenticationSender();
                            if (!$mailFrom || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
                                $mailFrom = $this->defaultMailFrom;
                            }

                            $email = (new Email())
                                ->from($mailFrom)
                                ->to($emailAddress)
                                ->subject('Mail sortant contenant un virus bloqué')
                                ->text('Un virus a été détecté dans un mail sortant. Utilisateur concerné: ' . $fromAddr);

                            $mailer->send($email);
                        } else {
                            $this->output->writeln('No email address found for user: ' . $user->getId());
                        }
                    }

                    $this->entityManager->flush();
                    $this->output->writeln('Alerts created and persisted for users with ROLE_SUPER_ADMIN or ROLE_ADMIN');

                } elseif ($target === 'user') {
                    if ($senderUser) {
                        if ($senderUser->getDomain()->getSendUserAlerts() === false) {
                            $this->output->writeln('Alerts disabled for user: ' . $senderUser->getId());
                        } else {
                            $alert = new Alert();
                            $alert->setAlertType('out_msgs');
                            $alert->setRefId($mailId);
                            $alert->setDate(new \DateTime('now', $timezone));
                            $alert->setSubject('Votre mail sortant a été bloqué');
                            $alert->setIsRead(false);
                            $alert->setUser($senderUser);
                            $alert->setRefUser($fromAddr);

                            $this->entityManager->persist($alert);
                        }

                        // Send email to sender user
                        if ($senderUser->getDomain()->getSendUserMailAlerts() === false) {
                            $this->output->writeln('Mail alerts disabled for user: ' . $senderUser->getId());
                        } else {
                            $mailFrom = $senderUser->getDomain()->getMailAuthenticationSender();
                            if (!$mailFrom || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
                                $mailFrom = $this->defaultMailFrom;
                            }
                            $email = (new Email())
                                ->from($mailFrom)
                                ->to($senderUser->getEmailFromRessource())
                                ->subject('Votre mail sortant a été bloqué')
                                ->text('Un virus a été détecté dans votre mail sortant.');

                            $smtpServer = $senderUser->getDomain()->getSrvSmtp();
                            $smtpPort = $senderUser->getDomain()->getSmtpPort();
                            $transport = Transport::fromDsn('smtp://' . $smtpServer . ':' . $smtpPort);
                            $mailer = new Mailer($transport);
                            $mailer->send($email);
                        }

                        $this->entityManager->flush();
                        $this->output->writeln('Alerts created and persisted for sender user');
                    } else {
                        $this->output->writeln('No user found with email: ' . $fromAddr);
                    }
                }
            } else {
                $this->output->writeln('OutMsg content is not "V"');
            }
        } else {
            $this->output->writeln('OutMsg not found for mail_id: ' . $mailId);
        }
    }

    private function handleSqlLimitReport(CreateAlertMessage $message)
    {
        $id = $message->getId();
        $reportDate = \DateTime::createFromFormat('Y-m-d H:i:s', $id);

        if ($reportDate === false) {
            // Handle the error if the date format is incorrect
            throw new \Exception('Invalid date format for ID: ' . $id);
        }
        $this->output->writeln('Handler invoked for SqlLimitReport Date: ' . $reportDate->format('Y-m-d H:i:s'));

        $reports = $this->entityManager->getRepository(SqlLimitReport::class)->findBy(['date' => $reportDate]);

        if (count($reports) > 0) {
            $this->output->writeln('Number of SqlLimitReport entries found: ' . count($reports));

            $timezone = new \DateTimeZone('Europe/Paris');

            // Collect all sender users' email addresses
            $senderEmails = [];
            foreach ($reports as $report) {
                $fromAddr = $report->getMailId();
                if (!in_array($fromAddr, $senderEmails)) {
                    $senderEmails[] = $fromAddr;
                }
            }

            $target = $message->getTarget();

            if ($target === 'admin') {
                // Fetch users with ROLE_SUPER_ADMIN or ROLE_ADMIN
                $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
                    ->where('u.roles LIKE :superAdminRole')
                    ->orWhere('u.roles LIKE :adminRole')
                    ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%')
                    ->setParameter('adminRole', '%"ROLE_ADMIN"%')
                    ->getQuery()
                    ->getResult();

                // Create a single alert for all admins and superadmins
                foreach ($users as $user) {
                    $alert = new Alert();
                    $alert->setAlertType('sql_limit_report');
                    $alert->setRefId($id);
                    $alert->setDate(new \DateTime('now', $timezone));
                    $alert->setSubject('Limite de quota franchie');
                    $alert->setIsRead(false);
                    $alert->setUser($user);
                    $alert->setRefUser(implode(', ', $senderEmails));

                    $this->entityManager->persist($alert);

                    // Send email to admin/superadmin
                    $emailAddress = $user->getEmailFromRessource();
                    if ($emailAddress !== null) {
                        $smtpServer = $user->getDomain()->getSrvSmtp();
                        $smtpPort = $user->getDomain()->getSmtpPort();
                        $transport = Transport::fromDsn('smtp://' . $smtpServer . ':' . $smtpPort);
                        $mailer = new Mailer($transport);

                        $mailFrom = $user->getDomain()->getMailAuthenticationSender();
                        if (!$mailFrom || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
                            $mailFrom = $this->defaultMailFrom;
                        }

                        $email = (new Email())
                            ->from($mailFrom)
                            ->to($emailAddress)
                            ->subject('Limite de quota franchie')
                            ->text('Des mails ont été bloqués en raison de quota dépassés. Utilisateurs concernés: ' . implode(', ', $senderEmails));

                        $mailer->send($email);
                    } else {
                        $this->output->writeln('No email address found for user: ' . $user->getId());
                    }
                }

            } elseif ($target === 'user') {

                // Create an alert for each senderUser
                $processedSenders = [];
                foreach ($reports as $report) {
                    $fromAddr = $report->getMailId();
                    if (!isset($processedSenders[$fromAddr])) {
                        $senderUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $fromAddr]);
                        if ($senderUser) {
                            $processedSenders[$fromAddr] = true;

                            if ($senderUser->getDomain()->getSendUserAlerts() === false) {
                                $this->output->writeln('Alerts disabled for user: ' . $senderUser->getId());
                            } else {
                                $alert = new Alert();
                                $alert->setAlertType('sql_limit_report');
                                $alert->setRefId($id);
                                $alert->setDate(new \DateTime('now', $timezone));
                                $alert->setSubject('Vous avez dépassé le quota');
                                $alert->setIsRead(false);
                                $alert->setUser($senderUser);
                                $alert->setRefUser($fromAddr);

                                $this->entityManager->persist($alert);
                            }

                            // Send email to sender user
                            if ($senderUser->getDomain()->getSendUserMailAlerts() === false) {
                                $this->output->writeln('Mail alerts disabled for user: ' . $senderUser->getId());
                            } else {
                                $mailFrom = $senderUser->getDomain()->getMailAuthenticationSender();
                                if (!$mailFrom || !filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
                                    $mailFrom = $this->defaultMailFrom;
                                }
                                $email = (new Email())
                                    ->from($mailFrom)
                                    ->to($senderUser->getEmailFromRessource())
                                    ->subject('Vous avez dépassé le quota')
                                    ->text('Vous avez dépassé le quota. Votre mail n\'a pas été distribué.');

                                $smtpServer = $senderUser->getDomain()->getSrvSmtp();
                                $smtpPort = $senderUser->getDomain()->getSmtpPort();
                                $transport = Transport::fromDsn('smtp://' . $smtpServer . ':' . $smtpPort);
                                $mailer = new Mailer($transport);
                                $mailer->send($email);
                            }
                        } else {
                            $this->output->writeln('No user found with email: ' . $fromAddr);
                        }
                    }
                }
            }

            $this->entityManager->flush();
            $this->output->writeln('Alerts created and persisted for users with ROLE_SUPER_ADMIN or ROLE_ADMIN');
        } else {
            $this->output->writeln('No SqlLimitReport entries found for Date: ' . $reportDate->format('Y-m-d H:i:s'));
        }
    }
}