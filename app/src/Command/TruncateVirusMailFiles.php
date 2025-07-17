<?php

namespace App\Command;

use App\Entity\Log;
use App\Entity\Msgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'agentj:truncate-virus-queue',
    description: 'Truncate amavis quarantine files older than X days (see .ENV file) ',
)]
class TruncateVirusMailFiles extends Command
{
    private int $nbDays = 30;

    public function __construct(
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleteFiles = [];
        if (!$this->params->get('app.amavis_quarantine_dir')) {
            throw new MissingMandatoryParametersException('The amavis_quarantine_dir parameter is missing ');
        }
        $amavisQuarantDir = $this->params->get('app.amavis_quarantine_dir');
        if (!file_exists($amavisQuarantDir)) {
            throw new FileNotFoundException($amavisQuarantDir);
        }

        $defaultNbDays = $this->params->get('app.amavis_quarantine_nbdays_before_delete');
        $days = $defaultNbDays ? $defaultNbDays : $this->nbDays;
        if ($days <= 10) {
            $days = $this->nbDays;
        }

        $folders = scandir($amavisQuarantDir);
        if ($folders === false) {
            throw new AccessDeniedException($amavisQuarantDir);
        }

        $folders = array_diff($folders, ['..', '.']);
        foreach ($folders as $folder) {
            $files = scandir($amavisQuarantDir . $folder);
            if ($files === false) {
                continue;
            }

            $files = array_diff($files, ['..', '.']);

            foreach ($files as $file) {
                $datemod = new \DateTime();
                $filePath = $amavisQuarantDir . $folder . '/' . $file;
                $modificationTime = filemtime($filePath);
                if ($modificationTime !== false) {
                    $datemod->setTimestamp($modificationTime);
                }
                $now = new \DateTime();
                $interval = $now->diff($datemod);
                if ($interval->format('%a') > $days) {
                    unlink($filePath);
                    $deleteFiles[] = $filePath;
                }
            }
        }
        return Command::SUCCESS;
    }
}
