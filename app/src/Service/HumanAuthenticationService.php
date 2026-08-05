<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Message;
use App\Entity\MessageRecipient;
use App\Repository\DomainRepository;
use App\Repository\MessageRecipientRepository;
use App\Repository\MessageRepository;

class HumanAuthenticationService
{
    public function __construct(
        private CryptEncryptService $cryptEncryptService,
        private DomainRepository $domainRepository,
        private MessageRepository $messageRepository,
        private MessageRecipientRepository $messageRecipientRepository,
    ) {
    }

    /**
     * @return ?array{?Message, ?Domain, MessageRecipient[]}
     */
    public function decryptToken(string $token): ?array
    {
        list($expiry, $decryptedToken) = $this->cryptEncryptService->decrypt($token);

        if ($expiry < time()) {
            return null;
        }

        if ($decryptedToken === false) {
            return null;
        }

        $tokenParts = explode('%%%', $decryptedToken);

        if (count($tokenParts) < 4) {
            return null;
        }

        $mailId = $tokenParts[0];
        $partitionTag = (int) $tokenParts[2];
        $domainId = $tokenParts[3];

        $message = $this->messageRepository->findOneByMailId($partitionTag, $mailId);
        $domain = $this->domainRepository->find($domainId);

        $messageRecipients = [];
        if ($message) {
            $messageRecipients = $this->messageRecipientRepository->findByMessage($message);

            $messageRecipients = array_filter(
                $messageRecipients,
                function (MessageRecipient $messageRecipient) use ($domain) {
                    return $messageRecipient->getRid()->getReverseDomain() == $domain->getDomain();
                },
            );
        }

        return [$message, $domain, $messageRecipients];
    }
}
