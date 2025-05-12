<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Log;

class LogService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Add entry to the log table
     */
    public function addLog(string $action, string $mailId = '', string $details = ''): bool
    {
        $log = new Log();
        $log->setAction($action);
        $log->setMailId($mailId);
        $log->setDetails($details);
        $this->em->persist($log);
        $this->em->flush();
        return true;
    }
}
