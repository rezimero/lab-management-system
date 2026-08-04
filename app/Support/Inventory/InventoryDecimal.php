<?php

namespace App\Support\Inventory;

use InvalidArgumentException;

final class InventoryDecimal
{
    public const SCALE = 6;

    public static function normalize(
        string|int|float $value,
        int $scale = self::SCALE
    ): string {
        $raw = trim((string) $value);

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $raw)) {
            throw new InvalidArgumentException('Invalid decimal value.');
        }

        $negative = str_starts_with($raw, '-');
        $unsigned = ltrim($raw, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException('Decimal precision exceeds the supported scale.');
        }

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($fraction, $scale, '0');
        $normalized = $whole.'.'.$fraction;

        return $negative && $normalized !== '0.'.str_repeat('0', $scale)
            ? '-'.$normalized
            : $normalized;
    }

    public static function add(string $left, string $right): string
    {
        return self::fromScaledInteger(
            self::toScaledInteger($left) + self::toScaledInteger($right)
        );
    }

    public static function subtract(string $left, string $right): string
    {
        return self::fromScaledInteger(
            self::toScaledInteger($left) - self::toScaledInteger($right)
        );
    }

    public static function compare(string $left, string $right): int
    {
        return self::toScaledInteger($left) <=> self::toScaledInteger($right);
    }

    public static function negate(string $value): string
    {
        return self::fromScaledInteger(-self::toScaledInteger($value));
    }

    public static function isPositive(string $value): bool
    {
        return self::compare($value, '0') > 0;
    }

    private static function toScaledInteger(string $value): int
    {
        $normalized = self::normalize($value);
        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = explode('.', $unsigned, 2);
        $digits = ltrim($whole.$fraction, '0');
        $integer = $digits === '' ? 0 : (int) $digits;

        return $negative ? -$integer : $integer;
    }

    private static function fromScaledInteger(int $value): string
    {
        $negative = $value < 0;
        $digits = (string) abs($value);
        $digits = str_pad($digits, self::SCALE + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -self::SCALE);
        $fraction = substr($digits, -self::SCALE);
        $result = $whole.'.'.$fraction;

        return $negative && $value !== 0 ? '-'.$result : $result;
    }
}
