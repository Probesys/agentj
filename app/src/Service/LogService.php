<?php
namespace App\Service;
use Doctrine\ORM\EntityManager;
use App\Entity\Log;

class LogService
{
    private $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Add entry to the log table
     * @param type $action
     * @param type $mailId
     * @param type $details
     * @return boolean
     */
    public function addLog($action, $mailId='',$details='')
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
