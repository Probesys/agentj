<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Log;
use App\Entity\MessageRecipient;
use App\Entity\User;
use App\Model\LogAction;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Util\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

class LogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DomainRepository $domainRepository,
        private UserRepository $userRepository,
        private Security $security,
    ) {
    }

    /**
     * Add entry to the log table
     */
    public function addLog(
        LogAction $action,
        ?string $mailId = null,
        string $details = '',
        ?Domain $domain = null,
    ): bool {
        $log = new Log();
        $log->setAction($action->value);
        $log->setMailId($mailId);
        $log->setDetails($details);
        $log->setDomain($domain);
        $log->setCreatedBy($this->findAuthor());
        $this->em->persist($log);
        $this->em->flush();
        return true;
    }

    /**
     * Add entry to the log table for an action on a message recipient, with
     * the domain of the recipient address.
     */
    public function addLogForRecipient(
        LogAction $action,
        MessageRecipient $messageRecipient,
        string $details = '',
    ): bool {
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

    private function findAuthor(): ?User
    {
        $token = $this->security->getToken();

        if ($token instanceof SwitchUserToken) {
            $originalUser = $token->getOriginalToken()->getUser();
            if (!$originalUser instanceof User) {
                return null;
            }

            return $this->userRepository->find($originalUser->getId());
        }

        $user = $token?->getUser();

        return $user instanceof User ? $user : null;
    }
}
