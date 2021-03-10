<?php

namespace App\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;


class TruncateVirusMailFiles extends ContainerAwareCommand {

  protected static $defaultName = 'agentj:truncate-virus-queue';
  private $nbDays = 30;

  protected function configure() {
    $this->setDescription('Truncate amavis quarantine files older than X days (see .ENV file)  ');
    
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $deleteFiles = [];
    if (!$this->getContainer()->getParameter('app.amavis_quarantine_dir')){
      throw new MissingMandatoryParametersException('The amavis_quarantine_dir parameter is missing ');
    }
    $amavisQuarantDir = $this->getContainer()->getParameter('app.amavis_quarantine_dir');
    if (!file_exists($amavisQuarantDir))
    {
      throw new FileNotFoundException($amavisQuarantDir);
    }

    $days = $this->getContainer()->getParameter('app.amavis_quarantine_nbdays_before_delete') ? $this->getContainer()->getParameter('app.amavis_quarantine_nbdays_before_delete') : $this->nbDays;
    if ($days <= 10 ) {
        $days = $this->nbDays;
    }
    
    $folders = array_diff(scandir($amavisQuarantDir), array('..', '.'));
    foreach ($folders as $folder){
      $files = array_diff(scandir($amavisQuarantDir . $folder), array('..', '.'));;
      foreach ($files as $file){
        $datemod = new \DateTime();
        $filePath = $amavisQuarantDir . $folder . '/' . $file;
        $datemod->setTimestamp(filemtime($filePath));
        $now = new \DateTime;
        $interval = $now->diff($datemod);
        if ($interval->format('%a') > $days){
          unlink($filePath);
          $deleteFiles[] = $filePath;
        }
        
      }
    }
  }
}
