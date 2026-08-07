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
            'user' => UserFactory::new(),
            'senderRuleAddress' => RuleAddressFactory::new(),
            'wb' => self::faker()->randomElement([
                ' ',
                'B',
                'W',
                '0',
                '',
            ]),
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

            $rule->setWb($attributes['wb']);
            $rule->setPriority($attributes['priority']);

            return $rule;
        });
    }
}
