<?php
namespace App\MessageHandler;

use App\Message\CreateAlertMessage;
use App\Entity\OutMsg;
use App\Entity\Alert;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Doctrine\ORM\UnitOfWork;

// to run the job type this in the docker :
//  /usr/bin/php /var/www/agentj/bin/console app:check-out-msgs
// and this in another docker :
// php bin/console messenger:consume doctrine --time-limit=3600 --memory-limit=128M -vv
class CreateAlertMessageHandler
{
    private EntityManagerInterface $entityManager;
    private ConsoleOutput $output;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->output = new ConsoleOutput();
    }

    public function __invoke(CreateAlertMessage $message)
    {
        $mailId = $message->getMailId();
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
                // Fetch users with ROLE_SUPER_ADMIN or ROLE_ADMIN
                $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
                    ->where('u.roles LIKE :superAdminRole')
                    ->orWhere('u.roles LIKE :adminRole')
                    ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%')
                    ->setParameter('adminRole', '%"ROLE_ADMIN"%')
                    ->getQuery()
                    ->getResult();

                // Fetch the user who sent the email based on from_addr
                $fromAddr = $outMsg->getFromAddr();
                $senderUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $fromAddr]);

                $timezone = new \DateTimeZone('Europe/Paris');

                foreach ($users as $user) {
                    $alert = new Alert();
                    $alert->setAlertType('out_msgs');
                    $alert->setRefId($mailId);
                    $alert->setDate(new \DateTime('now', $timezone));
                    $alert->setSubject('New out_msg with content "V"');
                    $alert->setIsRead(false);
                    $alert->setUser($user);
                    $alert->setRefUser($fromAddr);

                    $this->entityManager->persist($alert);

                    // Will also need to send them a mail here.
                }

                if ($senderUser) {
                    $alert = new Alert();
                    $alert->setAlertType('out_msgs');
                    $alert->setRefId($mailId);
                    $alert->setDate(new \DateTime('now', $timezone));
                    $alert->setSubject('New out_msg with content "V"');
                    $alert->setIsRead(false);
                    $alert->setUser($senderUser);
                    $alert->setRefUser($fromAddr);

                    $this->entityManager->persist($alert);

                    // Will also need to send them a mail here.
                } else {
                    $this->output->writeln('No user found with email: ' . $fromAddr);
                }

                // Mark the OutMsg as processed
                $outMsg->setProcessed(true);
                $this->entityManager->persist($outMsg);
                $this->output->writeln('OutMsg marked as processed: ' . ($outMsg->isProcessed() ? 'Yes' : 'No'));

                // Check the state of the entity
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $entityState = $unitOfWork->getEntityState($outMsg);
                $this->output->writeln('Entity state before flush: ' . $entityState);

                // Explicitly merge the entity if it is not managed
                if ($entityState !== UnitOfWork::STATE_MANAGED) {
                    $outMsg = $this->entityManager->merge($outMsg);
                    $this->output->writeln('OutMsg merged');
                }
                try {
                    $this->entityManager->flush();
                    $this->output->writeln('EntityManager flushed successfully');

                    // Verify the processed state after flush
                    $this->entityManager->refresh($outMsg);
                    $this->output->writeln('OutMsg processed state after flush: ' . ($outMsg->isProcessed() ? 'Yes' : 'No'));
                } catch (\Exception $e) {
                    $this->output->writeln('Error during EntityManager flush: ' . $e->getMessage());
                }

                $this->output->writeln('Alerts created and persisted for users with ROLE_SUPER_ADMIN or ROLE_ADMIN');
            } else {
                $this->output->writeln('OutMsg content is not "V"');
            }
        } else {
            $this->output->writeln('OutMsg not found for mail_id: ' . $mailId);
        }
    }
}