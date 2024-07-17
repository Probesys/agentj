<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

#[AsCommand(
    name: 'agentj:truncate-virus-queue',
    description: 'Truncate amavis quarantine files older than X days (see .ENV file) ',
)]
class TruncateVirusMailFiles extends Command
{

    private $nbDays = 30;

    protected function configure():void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $deleteFiles = [];
        if (!$this->getApplication()->getKernel()->getContainer()->getParameter('app.amavis_quarantine_dir')) {
            throw new MissingMandatoryParametersException('The amavis_quarantine_dir parameter is missing ');
        }
        $amavisQuarantDir = $this->getApplication()->getKernel()->getContainer()->getParameter('app.amavis_quarantine_dir');
        if (!file_exists($amavisQuarantDir)) {
            throw new FileNotFoundException($amavisQuarantDir);
        }

        $days = $this->getApplication()->getKernel()->getContainer()->getParameter('app.amavis_quarantine_nbdays_before_delete') ? $this->getApplication()->getKernel()->getContainer()->getParameter('app.amavis_quarantine_nbdays_before_delete') : $this->nbDays;
        if ($days <= 10) {
            $days = $this->nbDays;
        }

        $folders = array_diff(scandir($amavisQuarantDir), ['..', '.']);
        foreach ($folders as $folder) {
            $files = array_diff(scandir($amavisQuarantDir . $folder), ['..', '.']);
            ;
            foreach ($files as $file) {
                $datemod = new \DateTime();
                $filePath = $amavisQuarantDir . $folder . '/' . $file;
                $datemod->setTimestamp(filemtime($filePath));
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
