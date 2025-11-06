<?php

namespace App\Entity\Trait;

trait ResourceToStringTrait
{
    protected function convertResourceToString(mixed $value, bool $rewind = false): ?string
    {
        $stringValue = $value;

        if (is_resource($value)) {
            $stringValue = stream_get_contents($value, -1, 0);

            if ($stringValue !== false && $rewind) {
                rewind($value);
            }
        }

        if (is_string($stringValue)) {
            return $stringValue;
        }

        return null;
    }
}
