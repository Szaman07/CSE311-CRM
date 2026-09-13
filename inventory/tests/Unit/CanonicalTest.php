<?php

namespace Tests\Unit;

use App\Support\Canonical;
use App\Support\LocalDateRange;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CanonicalTest extends TestCase
{
    #[DataProvider('moneyCases')]
    public function test_money_is_exact_and_canonical(string $input, string $expected): void
    {
        $this->assertSame($expected, Canonical::money($input));
        $this->assertSame($expected, Canonical::formatCents(Canonical::cents($input)));
    }

    public static function moneyCases(): array
    {
        return [['0', '0.00'], ['7.1', '7.10'], ['99999999.99', '99999999.99']];
    }

    public function test_money_rejects_float_style_and_excess_precision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Canonical::money('01.999');
    }

    public function test_canonical_fingerprint_is_order_sensitive_but_repeatable(): void
    {
        $a = ['actor_id' => '1', 'items' => [['product_id' => '2', 'quantity' => 1]]];
        $this->assertSame(Canonical::fingerprint($a), Canonical::fingerprint($a));
        $this->assertNotSame(Canonical::fingerprint($a), Canonical::fingerprint(array_reverse($a, true)));
    }

    public function test_dhaka_dates_become_half_open_utc_range(): void
    {
        [$from, $until] = LocalDateRange::toUtc('2026-09-01', '2026-09-01');
        $this->assertSame('2026-08-31 18:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 18:00:00', $until->format('Y-m-d H:i:s'));
    }
}
