<?php

namespace App\Util;

abstract class Enum
{
    /**
     * @return array<string, string>
     */
    public static function all(bool $swapKeyAndValue = false): array
    {
        $array = (new \ReflectionClass(static::class))->getConstants();

        return $swapKeyAndValue ? array_flip($array) : $array;
    }

    /**
     * @return array<int, string>
     */
    public static function allValues(): array
    {
        $array = (new \ReflectionClass(static::class))->getConstants();
        $array = array_combine($array, array_values($array));
        
        return $array;
    }

    /**
     * @return array<int, string>
     */
    public static function values(bool $swapKeyAndValue = false): array
    {
        $array = array_values((new \ReflectionClass(static::class))->getConstants());

        return $swapKeyAndValue ? array_flip($array) : $array;
    }

    /**
     * @return mixed|false
     */
    public static function value(string $key)
    {
        return (new \ReflectionClass(static::class))->getConstant($key);
    }
}
