<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The restaurant's shifts run overnight (e.g. 8pm to 3pm the next day), so
 * the trading day doesn't reset at midnight — it runs 06:00 through 05:59:59
 * the next calendar day. A sale at 1am still belongs to the PREVIOUS
 * business day, not the new calendar date. Every "today" / date-range
 * calculation for orders, sales, and tokens should go through this class
 * instead of raw Carbon::today()/whereDate() so they all agree on the
 * boundary.
 */
class BusinessDay
{
    public const CUTOFF_HOUR = 6;

    /**
     * The business date a given moment falls in, normalized to midnight
     * (e.g. 2026-07-30 01:00 belongs to business date 2026-07-29).
     */
    public static function of(CarbonInterface|string|null $moment = null): Carbon
    {
        $moment = $moment ? Carbon::parse($moment) : Carbon::now();
        $date = $moment->copy()->startOfDay();

        return $moment->hour < self::CUTOFF_HOUR ? $date->subDay() : $date;
    }

    /** The business date "right now" currently belongs to. */
    public static function today(): Carbon
    {
        return self::of(Carbon::now());
    }

    /**
     * [start, end] range covering one business date — start inclusive at
     * 06:00:00, end inclusive at 05:59:59.999999 the next calendar day.
     */
    public static function boundsFor(CarbonInterface|string $date): array
    {
        $start = Carbon::parse($date)->startOfDay()->addHours(self::CUTOFF_HOUR);
        $end = $start->copy()->addDay()->subMicrosecond();

        return [$start, $end];
    }

    /** [start, end] range spanning every business date from $from through $to, inclusive. */
    public static function boundsBetween(CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        [$start] = self::boundsFor($from);
        [, $end] = self::boundsFor($to);

        return [$start, $end];
    }

    /** [start, end] range covering the whole business month a given date falls in. */
    public static function monthBoundsFor(CarbonInterface|string $anyDateInMonth): array
    {
        $date = Carbon::parse($anyDateInMonth);
        [$start] = self::boundsFor($date->copy()->startOfMonth());
        [, $end] = self::boundsFor($date->copy()->endOfMonth());

        return [$start, $end];
    }
}
