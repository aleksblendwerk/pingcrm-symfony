<?php

declare(strict_types=1);

namespace App\Util;

use Symfony\Component\HttpFoundation\ParameterBag;

class RequestHelper
{
    public static function stringOrNull(ParameterBag $bag, string $value): ?string
    {
        if ($bag->has($value) && is_string($bag->get($value))) {
            return $bag->get($value);
        }

        return null;
    }

    public static function intOrNull(ParameterBag $bag, string $value): ?int
    {
        if ($bag->has($value) && is_int($bag->get($value))) {
            return $bag->get($value);
        }

        return null;
    }

    public static function boolOrNull(ParameterBag $bag, string $value): ?bool
    {
        if ($bag->has($value) && is_bool($bag->get($value))) {
            return $bag->get($value);
        }

        return null;
    }
}
