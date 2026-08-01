<?php

use App\Support\Dates\DisplayDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config(['app.display_timezone' => 'Asia/Almaty']);
});

it('shifts a stored utc timestamp into the display timezone', function (): void {
    $storedUtc = Carbon::parse('2026-06-29 06:44:54', 'UTC');

    expect(DisplayDate::format($storedUtc, 'Y-m-d H:i'))->toBe('2026-06-29 11:44');
});

it('crosses the day boundary when the display timezone is a day ahead', function (): void {
    $storedUtc = Carbon::parse('2026-06-29 21:30:00', 'UTC');

    expect(DisplayDate::format($storedUtc, 'Y-m-d H:i'))->toBe('2026-06-30 02:30');
});

it('does not mutate the timestamp it was given', function (): void {
    $storedUtc = Carbon::parse('2026-06-29 06:44:54', 'UTC');

    DisplayDate::format($storedUtc, 'Y-m-d H:i');

    expect($storedUtc->format('Y-m-d H:i'))->toBe('2026-06-29 06:44')
        ->and($storedUtc->getTimezone()->getName())->toBe('UTC');
});

it('returns null for a missing timestamp', function (): void {
    expect(DisplayDate::format(null, 'Y-m-d H:i'))->toBeNull();
});

it('returns the start of the display day expressed in utc', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-29 21:30:00', 'UTC'));

    // 21:30 UTC is already 02:30 on 30 June in Almaty, so "today" started at 19:00 UTC on 29 June.
    expect(DisplayDate::startOfToday()->format('Y-m-d H:i'))->toBe('2026-06-29 19:00')
        ->and(DisplayDate::startOfToday()->getTimezone()->getName())->toBe('UTC');

    Carbon::setTestNow();
});
