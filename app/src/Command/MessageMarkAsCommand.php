<?php

namespace App\Command;

use App\Amavis\MessageStatus;
use App\Repository\MsgsRepository;
use App\Repository\MsgrcptRepository;
use App\Service\MessageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'agentj:messages:mark-as',
    description: 'Mark a message as spam or ham.',
)]
class MessageMarkAsCommand
{
    public function __construct(
        private MsgsRepository $messageRepository,
        private MsgrcptRepository $messageRecipientRepository,
        private MessageService $messageService,
        #[Autowire(param: 'app.spamassassin_learn_dir')]
        private string $spamassassinLearnDir,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument('The status to apply to the message (spam or ham).', suggestedValues: ['spam', 'ham'])]
        string $status,
        #[Argument('The mail id of the message to mark.')]
        string $mailId,
        #[Option('The partition tag of the message to mark.')]
        int $partitionTag = 0,
    ): int {
        if ($status !== 'spam' && $status !== 'ham') {
            $output->writeln('Status must either be "ham" or "spam".');
            return Command::FAILURE;
        }

        $message = $this->messageRepository->findOneByMailId($partitionTag, $mailId);

        if (!$message) {
            $output->writeln("Message with mail id {$mailId} (partition tag {$partitionTag}) does not exists.");
            return Command::FAILURE;
        }

        if ($status === 'spam') {
            $markDirName = 'spams';
        } else {
            $markDirName = 'hams';
        }

        $output->write('Putting the message in the SpamAssassin learning directory… ');

        $outputDirPath = "{$this->spamassassinLearnDir}/{$markDirName}";
        if (!is_dir($outputDirPath)) {
            mkdir($outputDirPath);
        }

        $content = $message->getQuarantineContent();

        $fileName = "{$message->getMailId()}.eml";
        $filePath = "{$outputDirPath}/{$fileName}";

        file_put_contents($filePath, $content);

        $output->writeln('ok');

        foreach ($message->getMsgRcpts() as $messageRecipient) {
            $recipient = $messageRecipient->getRid();
            $recipientEmail = $recipient->getEmailClear();

            if ($messageRecipient->isUntreated() && $status === 'spam') {
                $output->write("Moving the message to spams for {$recipientEmail}… ");

                $messageRecipient->setStatus(MessageStatus::SPAMMED);
                $this->messageRecipientRepository->save($messageRecipient);

                $output->writeln('ok');
            } elseif ($messageRecipient->isSpam() && $status === 'ham') {
                $output->write("Restoring the message for {$recipientEmail}… ");

                $this->messageService->restore($messageRecipient);

                $output->writeln('ok');
            }
        }

        return Command::SUCCESS;
    }
}
