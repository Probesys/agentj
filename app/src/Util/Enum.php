<?php

namespace App\Util;

abstract class Enum
{
    public static function all($swapKeyAndValue = false): array
    {
        $array = (new \ReflectionClass(static::class))->getConstants();

        return $swapKeyAndValue ? array_flip($array) : $array;
    }
    
    public static function allValues(): array
    {
        $array = (new \ReflectionClass(static::class))->getConstants();
        $array = array_combine($array, array_values($array));
        return $array;
    }
    

    public static function values($swapKeyAndValue = false): array
    {
        $array = array_values((new \ReflectionClass(static::class))->getConstants());

        return $swapKeyAndValue ? array_flip($array) : $array;
    }

    public static function value($key)
    {
        return (new \ReflectionClass(static::class))->getConstant($key);
    }
}
