<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class LocalDateRange
{
    public static function toUtc(?string $start, ?string $end, string $timezone = 'Asia/Dhaka'): array
    {
        if (! $start || ! $end) {
            throw new InvalidArgumentException('Both start and end dates are required.');
        }
        $from = CarbonImmutable::createFromFormat('!Y-m-d', $start, $timezone);
        $through = CarbonImmutable::createFromFormat('!Y-m-d', $end, $timezone);
        if (! $from || ! $through || $from->format('Y-m-d') !== $start || $through->format('Y-m-d') !== $end || $from->greaterThan($through)) {
            throw new InvalidArgumentException('Provide a valid date range with start before or equal to end.');
        }

        return [$from->utc(), $through->addDay()->utc()];
    }
}
