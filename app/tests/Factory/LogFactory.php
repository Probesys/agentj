<?php

namespace App\Tests\Factory;

use App\Entity\Log;
use App\Model\LogAction;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Log>
 */
final class LogFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Log::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'action' => LogAction::Restore->value,
            'mailId' => self::faker()->regexify('[a-zA-Z0-9]{12}'),
            'details' => '',
        ];
    }
}
