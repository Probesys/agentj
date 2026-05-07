<?php

namespace App\Service;

use App\Entity\Msgs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SpamassassinService
{
    public function __construct(
        #[Autowire(param: 'app.spamassassin_learn_dir')]
        private string $spamassassinLearnDir,
    ) {
    }

    /**
     * Put the message content in Spamassassin "spams" folder and return true on success.
     */
    public function marksAsSpam(Msgs $message): bool
    {
        return $this->putMessage($message, 'spams');
    }

    /**
     * Put the message content in Spamassassin "hams" folder and return true on success.
     */
    public function marksAsHam(Msgs $message): bool
    {
        return $this->putMessage($message, 'hams');
    }

    /**
     * @param 'spams'|'hams' $folder
     */
    private function putMessage(Msgs $message, string $folder): bool
    {
        $outputDirPath = "{$this->spamassassinLearnDir}/{$folder}";
        if (!is_dir($outputDirPath)) {
            mkdir($outputDirPath, permissions: 0755, recursive: true);
        }

        $content = $message->getQuarantineContent();

        $fileName = "{$message->getMailId()}.eml";
        $filePath = "{$outputDirPath}/{$fileName}";

        return file_put_contents($filePath, $content) !== false;
    }
}
