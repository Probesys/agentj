<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Log;
use App\Entity\MessageRecipient;
use App\Repository\DomainRepository;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;

class LogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DomainRepository $domainRepository,
    ) {
    }

    /**
     * Add entry to the log table
     */
    public function addLog(string $action, string $mailId = '', string $details = '', ?Domain $domain = null): bool
    {
        $log = new Log();
        $log->setAction($action);
        $log->setMailId($mailId);
        $log->setDetails($details);
        $log->setDomain($domain);
        $this->em->persist($log);
        $this->em->flush();
        return true;
    }

    /**
     * Add entry to the log table for an action on a message recipient, with
     * the domain of the recipient address.
     */
    public function addLogForRecipient(string $action, MessageRecipient $messageRecipient, string $details = ''): bool
    {
        return $this->addLog(
            $action,
            $messageRecipient->getMailId(),
            $details,
            $this->findRecipientDomain($messageRecipient),
        );
    }

    private function findRecipientDomain(MessageRecipient $messageRecipient): ?Domain
    {
        $email = $messageRecipient->getAddress()?->getEmail();
        $domainName = $email !== null ? Email::extractDomain($email) : null;

        if ($domainName === null) {
            return null;
        }

        return $this->domainRepository->findOneByDomain($domainName);
    }
}
