<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\SenderRule;
use App\Entity\User;
use App\Repository\RuleAddressRepository;
use App\Repository\SenderRuleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SenderRuleService
{
    public function __construct(
        private SenderAddressSanitizer $senderAddressSanitizer,
        private RuleAddressRepository $ruleAddressRepository,
        private SenderRuleRepository $senderRuleRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param 'accept'|'block' $rule
     */
    public function createOrUpdateForRecipient(
        string $address,
        User $recipient,
        string $rule,
        int $source,
    ): bool {
        return $this->createOrUpdateForRecipients($address, [$recipient], $rule, $source);
    }

    /**
     * @param 'accept'|'block' $rule
     */
    public function createOrUpdateForUserAndAliases(
        string $address,
        User $user,
        string $rule,
        int $source,
    ): bool {
        return $this->createOrUpdateForRecipients(
            $address,
            $this->userRepository->findUserAndAliases($user),
            $rule,
            $source,
        );
    }

    /**
     * @param User[] $recipients
     * @param 'accept'|'block' $rule
     */
    private function createOrUpdateForRecipients(
        string $address,
        array $recipients,
        string $rule,
        int $source,
    ): bool {
        $normalizedAddress = $this->senderAddressSanitizer->sanitize($address);
        if ($normalizedAddress === null) {
            return false;
        }

        $this->saveNormalized($normalizedAddress, $recipients, $rule, $source);

        return true;
    }

    /**
     * @param 'accept'|'block' $rule
     */
    public function importFile(string $path, Domain $domain, string $rule): void
    {
        $domainName = $domain->getDomain();
        $recipient = $domainName ? $this->userRepository->findDomainUser($domainName) : null;
        if (!$recipient) {
            throw new \LogicException('The domain user required to create sender rules does not exist.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open sender rules import file: {$path}");
        }

        $importedAddresses = [];

        try {
            while (($address = fgets($handle, 4096)) !== false) {
                $normalizedAddress = $this->senderAddressSanitizer->sanitize($address);
                if ($normalizedAddress === null || isset($importedAddresses[$normalizedAddress])) {
                    continue;
                }

                $this->saveNormalized(
                    $normalizedAddress,
                    [$recipient],
                    $rule,
                    SenderRule::TYPE_IMPORT,
                    flush: false,
                );
                $importedAddresses[$normalizedAddress] = true;
            }

            $this->entityManager->flush();
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param User[] $recipients
     * @param 'accept'|'block' $rule
     */
    private function saveNormalized(
        string $normalizedAddress,
        array $recipients,
        string $rule,
        int $source,
        bool $flush = true,
    ): void {
        $ruleAddress = $this->ruleAddressRepository->findOneOrCreateByEmail(
            $normalizedAddress,
            flush: false,
            priority: RuleAddressService::computePriority($normalizedAddress),
        );

        foreach ($recipients as $recipient) {
            $this->senderRuleRepository->updateOrCreateRule(
                $recipient,
                $ruleAddress,
                wbRule: $rule,
                type: $source,
                priority: SenderRule::PRIORITY_USER,
                flush: false,
            );
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
