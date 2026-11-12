<?php

namespace App\Command;

use App\Message\CreateAlertMessage;
use App\MessageHandler\CreateAlertMessageHandler;
use App\Repository\DomainRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Detect mailboxes receiving a flood of bounces.
 *
 * When a mailbox is compromised and used to send spam through the provider's
 * SMTP server (i.e. not through AgentJ), the only visible sign in AgentJ is
 * the flood of non-delivery notifications and auto-replies it receives.
 */
#[AsCommand(
    name: 'app:create-alert-for-bounce-flood',
    description: 'Detect mailboxes receiving a flood of bounces (probably compromised) and alert admins and users.',
)]
class CreateAlertForBounceFloodCommand extends Command
{
    public function __construct(
        private DomainRepository $domainRepository,
        private MessageRepository $messageRepository,
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string, int> $thresholds */
        $thresholds = [];
        foreach ($this->domainRepository->findAll() as $domain) {
            $threshold = $domain->getBounceFloodThreshold();
            $domainName = $domain->getDomain();
            if ($threshold > 0 && $domainName !== null) {
                $thresholds[strtolower($domainName)] = $threshold;
            }
        }

        if (empty($thresholds)) {
            $output->writeln('Bounce flood alerts are disabled for all the domains.');
            return Command::SUCCESS;
        }

        $since = new \DateTimeImmutable('-1 hour');
        $bounces = $this->messageRepository->countBouncesByRecipientSince($since, min($thresholds));

        foreach ($bounces as $email => $count) {
            $domainName = substr($email, strrpos($email, '@') + 1);
            $threshold = $thresholds[$domainName] ?? null;

            if ($threshold === null || $count < $threshold) {
                continue;
            }

            $output->writeln("Bounce flood detected for {$email}: {$count} bounces within the last hour.");

            $type = CreateAlertMessageHandler::TYPE_BOUNCE_FLOOD;
            $this->messageBus->dispatch(new CreateAlertMessage($type, $email, 'admin', $count));
            $this->messageBus->dispatch(new CreateAlertMessage($type, $email, 'user', $count));
        }

        return Command::SUCCESS;
    }
}
