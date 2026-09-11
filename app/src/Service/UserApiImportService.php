<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generic (non-CSV) user import used by the /api/users/import endpoint.
 * Rows are plain associative arrays decoded from a JSON request body:
 * {"email": "...", "firstName": "...", "lastName": "...", "group": "..."}
 * Only "email" is required; the domain part of each email must match the
 * authenticated Domain, so a leaked API key only ever affects its own domain.
 */
class UserApiImportService
{
    private const MAX_ROWS = 5000;

    public function __construct(
        private EntityManagerInterface $em,
        private GroupService $groupService,
    ) {
    }

    /**
     * @param array<int, mixed> $rows
     * @return array{imported: int, updated: int, errors: string[]}
     */
    public function importUsers(Domain $domain, array $rows): array
    {
        $em = $this->em;
        $imported = 0;
        $updated = 0;
        $errors = [];
        $groups = [];
        $seen = [];
        $domainSuffix = '@' . strtolower($domain->getDomain());
        $batchSize = 20;

        foreach (array_slice($rows, 0, self::MAX_ROWS) as $index => $row) {
            if (
                !is_array($row) || empty($row['email']) || !is_string($row['email'])
                || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)
            ) {
                $errors[] = "Row $index: missing or invalid 'email'";
                continue;
            }

            $email = strtolower($row['email']);
            if (!str_ends_with($email, $domainSuffix)) {
                $errors[] = "Row $index: email '$email' does not belong to domain '" . $domain->getDomain() . "'";
                continue;
            }

            if (isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $groupEntity = null;
            $groupName = isset($row['group']) && is_string($row['group']) ? trim($row['group']) : '';
            if ($groupName !== '') {
                if (!isset($groups[$groupName])) {
                    $groupEntity = $em->getRepository(Group::class)->findOneBy([
                        'domain' => $domain,
                        'name' => $groupName,
                    ]);
                    if (!$groupEntity) {
                        $groupEntity = new Group();
                        $groupEntity->setName($groupName);
                        $groupEntity->setDomain($domain);
                        $groupEntity->setWbRule('none');
                        if ($domain->getPolicy()) {
                            $groupEntity->setPolicy($domain->getPolicy());
                        }
                        $em->persist($groupEntity);
                    }
                    $groups[$groupName] = $groupEntity;
                } else {
                    $groupEntity = $groups[$groupName];
                }
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $updated++;
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($email);
                $user->setRoles('["ROLE_USER"]');
                $user->setDomain($domain);
                $imported++;
            }

            $firstName = isset($row['firstName']) && is_string($row['firstName']) ? trim($row['firstName']) : '';
            $lastName = isset($row['lastName']) && is_string($row['lastName']) ? trim($row['lastName']) : '';
            if ($firstName !== '' || $lastName !== '') {
                $user->setFullname(trim($firstName . ' ' . $lastName));
            }

            if ($groupEntity) {
                $user->addGroup($groupEntity);
            } elseif ($domain->getPolicy()) {
                $user->setPolicy($domain->getPolicy());
            }

            $em->persist($user);

            if ((($index + 1) % $batchSize) === 0) {
                $em->flush();
            }
        }

        $em->flush();
        $this->groupService->updateSenderRules();

        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * @param array<int, mixed> $emails
     * @return array{deleted: int, errors: string[]}
     */
    public function deleteUsers(Domain $domain, array $emails): array
    {
        $em = $this->em;
        $deleted = 0;
        $errors = [];
        $seen = [];
        $domainSuffix = '@' . strtolower($domain->getDomain());

        foreach (array_slice($emails, 0, self::MAX_ROWS) as $index => $email) {
            if (!is_string($email) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $index: invalid email";
                continue;
            }

            $email = strtolower($email);
            if (!str_ends_with($email, $domainSuffix)) {
                $errors[] = "Row $index: email '$email' does not belong to domain '" . $domain->getDomain() . "'";
                continue;
            }

            if (isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email, 'domain' => $domain]);
            if (!$user) {
                $errors[] = "Row $index: email '$email' not found";
                continue;
            }

            $em->remove($user);
            $deleted++;
        }

        $em->flush();
        $this->groupService->updateSenderRules();

        return ['deleted' => $deleted, 'errors' => $errors];
    }
}
