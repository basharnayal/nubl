<?php

namespace App\Support;

/**
 * SAR amounts with two decimal places using BCMath (no float accumulation).
 */
final class FinancialMath
{
    public const SCALE = 2;

    public static function normalize(string|int|float $amount): string
    {
        $s = match (true) {
            is_string($amount) => trim($amount),
            is_int($amount) => (string) $amount,
            default => sprintf('%.14F', $amount),
        };

        if ($s === '') {
            $s = '0';
        }

        return bcadd($s, '0', self::SCALE);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    public static function compare(string $a, string $b): int
    {
        return bccomp($a, $b, self::SCALE);
    }

    public static function min(string $a, string $b): string
    {
        return self::compare($a, $b) <= 0 ? $a : $b;
    }
}
