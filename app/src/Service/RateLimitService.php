<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\OutMessage;
use App\Entity\User;
use App\Repository\OutMessageRepository;
use DateTimeImmutable;

/**
 * @phpstan-type QuotaType 'user'|'group'|'domain'
 */
class RateLimitService
{
    public function __construct(
        private OutMessageRepository $outMessageRepository,
    ) {
    }

    /**
     * Computes usage for each user's applicable quotas.
     * Quotas can be defined in:
     *   - User
     *   - associated Group(s)
     *   - associated Domain
     *
     * @return array{
     *     type: QuotaType|null,
     *     usage: array<int, array{
     *         limit: int,
     *         period: int,
     *         current: int,
     *         remaining: int
     *     }>
     * }
     */
    public function getQuotaUsage(User $user, ?Address $userAddress = null): array
    {
        $result = $this->getApplicableQuotas($user);
        $type = $result['type'] ?? null;
        $quotas = $result['quotas'] ?? [];

        if (empty($quotas)) {
            return [
                'type' => null,
                'usage' => [],
            ];
        }

        $queryTime = new DateTimeImmutable();

        // If user has not sent messages yet, compute stats with no messages
        if (!$userAddress) {
            return [
                'type' => $type,
                'usage' => $this->computeQuotasUsage($quotas, $queryTime, []),
            ];
        }

        $window = $this->getHighestQuotaPeriod($quotas);

        $cutoff = $queryTime->modify("-{$window} seconds")->getTimestamp();

        // Retrieve out messages for the widest period of all quotas
        $messages = $this->outMessageRepository
            ->createQueryBuilder('m')
            ->andWhere('m.senderAddress = :address')
            ->andWhere('m.timeNum >= :cutoff')
            ->setParameter('address', $userAddress)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('m.timeNum', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'type' => $type,
            'usage' => $this->computeQuotasUsage($quotas, $queryTime, $messages),
        ];
    }

    /**
     * Get applicable quotas for a given User.
     * @return array{
     *     type: QuotaType,
     *     quotas: list<array<string, int>>
     * }|null
     */
    public function getApplicableQuotas(User $user): ?array
    {
        if ($quotas = $user->getQuota()) {
            return [
                'type' => 'user',
                'quotas' => array_slice($quotas, 0, 3),
            ];
        }

        if ($groups = $user->getGroups()) {
            $quotas = [];
            foreach ($groups as $group) {
                $quotas = array_merge($quotas, $group->getQuota() ?? []);
            }

            if (count($quotas) > 0) {
                return [
                    'type' => 'group',
                    'quotas' => array_slice($quotas, 0, 3),
                ];
            }
        };

        if ($domain = $user->getDomain()) {
            if ($quotas = $domain->getQuota()) {
                return [
                    'type' => 'domain',
                    'quotas' => array_slice($quotas, 0, 3),
                ];
            }
        }

        return null;
    }

    /**
     * Compute the widest time period for all quotas.
     *
     * @param list<array<string, int>> $quotas
     */
    private function getHighestQuotaPeriod(array $quotas): int
    {
        usort($quotas, function ($a, $b) {
            return $b['quota_seconds'] <=> $a['quota_seconds'];
        });

        return $quotas[0]['quota_seconds'];
    }

    /**
     * Compute usage for quotas.
     *
     * @param list<array<string, int>> $quotas
     * @param DateTimeImmutable $queryTime Time the request was made
     * @param OutMessage[] $messages Messages that are in widest quota time window
     * @return array<int, array{
     *     limit: int,
     *     period: int,
     *     current: int,
     *     remaining: int
     * }>
     */
    private function computeQuotasUsage(
        array $quotas,
        DateTimeImmutable $queryTime,
        array $messages,
    ): array {
        $usage = [];

        foreach ($quotas as $quota) {
            $period = $quota['quota_seconds'];
            $limit = $quota['quota_emails'];

            // If no messages, quota is untouched.
            if (empty($messages)) {
                $usage[] = [
                    'limit' => $limit,
                    'period' => $period,
                    'current' => 0,
                    'remaining' => $limit,
                ];
                // Pass to next quota.
                continue;
            }

            $quotaCutoff = $queryTime
                ->modify("-{$period} seconds")
                ->getTimestamp();

            $used = count(array_filter(
                $messages,
                static fn (OutMessage $message): bool =>
                    $message->getTimeNum() >= $quotaCutoff,
            ));

            $usage[] = [
                'limit' => $limit,
                'period' => $period,
                'current' => $used,
                'remaining' => max(0, $limit - $used),
            ];
        }

        return $usage;
    }
}
