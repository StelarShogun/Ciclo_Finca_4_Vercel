<?php

namespace App\Support;

final class AdminPerPage
{
    public const ALLOWED = [10, 25, 50];

    public static function resolve(mixed $raw): int
    {
        $value = (int) $raw;

        return in_array($value, self::ALLOWED, true) ? $value : 10;
    }
}
