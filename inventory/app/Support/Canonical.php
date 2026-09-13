<?php

namespace App\Support;

use InvalidArgumentException;
use Normalizer;

final class Canonical
{
    public static function text(string $value): string
    {
        $value = trim($value);

        return class_exists(Normalizer::class) ? (Normalizer::normalize($value, Normalizer::FORM_C) ?: $value) : $value;
    }

    public static function category(string $value): string
    {
        return mb_strtolower(self::text($value), 'UTF-8');
    }

    public static function email(string $value): string
    {
        return mb_strtolower(self::text($value), 'UTF-8');
    }

    public static function sku(string $value): string
    {
        $sku = strtoupper(trim($value));
        if (! preg_match('/^[A-Z0-9_-]{1,64}$/', $sku)) {
            throw new InvalidArgumentException('SKU must contain only A-Z, 0-9, hyphen, or underscore.');
        }

        return $sku;
    }

    public static function money(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?$/', $value, $m)) {
            throw new InvalidArgumentException('Money must be a nonnegative decimal with at most two fractional digits.');
        }

        return $m[1].'.'.str_pad($m[2] ?? '', 2, '0');
    }

    public static function cents(string $normalizedMoney): int
    {
        [$whole, $fraction] = explode('.', self::money($normalizedMoney));

        return ((int) $whole * 100) + (int) $fraction;
    }

    public static function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function fingerprint(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
