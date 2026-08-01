<?php

namespace App\Support\Dates;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Timestamps are stored in UTC. This converts them to the timezone the
 * application is operated in, and is the only place that conversion
 * should happen: right before a value is shown to a human.
 */
final class DisplayDate
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone');
    }

    /**
     * Format a stored UTC timestamp for display, or return null when it is not set.
     */
    public static function format(?DateTimeInterface $dateTime, string $format): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return Carbon::instance($dateTime)->timezone(self::timezone())->format($format);
    }

    /**
     * The current moment in the display timezone.
     */
    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    /**
     * Midnight of the current display-timezone day, expressed in UTC so it can
     * be compared against the UTC values stored in the database.
     */
    public static function startOfToday(): Carbon
    {
        return self::now()->startOfDay()->utc();
    }
}
