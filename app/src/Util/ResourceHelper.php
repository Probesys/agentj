<?php

namespace App\Util;

abstract class ResourceHelper
{
    public static function toString(
        mixed $value,
        bool $rewind = false
    ): ?string {
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
