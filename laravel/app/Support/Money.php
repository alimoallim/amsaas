<?php

namespace App\Support;

final class Money
{
    public const SCALE = 4;

    public static function zero(): string
    {
        return bcadd('0', '0', self::SCALE);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }

    public static function div(string $a, string $b): string
    {
        if (bccomp($b, '0', self::SCALE) === 0) {
            return self::zero();
        }

        return bcdiv($a, $b, self::SCALE);
    }

    public static function comp(string $a, string $b): int
    {
        return bccomp($a, $b, self::SCALE);
    }

    public static function toScale(string $amount, int $scale = self::SCALE): string
    {
        return bcadd($amount, '0', $scale);
    }

    public static function max(string $a, string $b): string
    {
        return bccomp($a, $b, self::SCALE) >= 0 ? $a : $b;
    }

    public static function min(string $a, string $b): string
    {
        return bccomp($a, $b, self::SCALE) <= 0 ? $a : $b;
    }
}
