<?php

namespace App\Tests\Factory;

use App\Entity\SenderRule;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SenderRule>
 */
final class SenderRuleFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return SenderRule::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new()->user(),
            'senderRuleAddress' => RuleAddressFactory::new(),
            'wb' => self::faker()->randomElement([
                'accept',
                'block',
                'allow',
                'enabled',
                'none',
            ]),
            'type' => null,
            'group' => null,
            'priority' => SenderRule::PRIORITY_USER,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes) {
            $rule = new SenderRule(
                $attributes['user'],
                $attributes['senderRuleAddress'],
            );

            $rule->setGroup($attributes['group'] ?? null);
            $rule->setWbRule($attributes['wb']);
            if ($attributes['type'] !== null) {
                $rule->setType($attributes['type']);
            }
            $rule->setPriority($attributes['priority']);

            return $rule;
        });
    }
}
