<?php

use App\Models\Language;
use Illuminate\Support\Number;
use Tests\TestCase;

uses(TestCase::class);

it('resolves the admin locale from available translation directories', function (): void {
    expect(Language::resolveAdminLocale('kk'))->toBe('kk');
});

it('falls back to the configured fallback locale when translations are unavailable', function (): void {
    expect(Language::resolveAdminLocale('de'))->toBe(config('app.fallback_locale'));
});

it('formats numbers for the kk language code without intl errors', function (): void {
    app()->setLocale(Language::resolveAdminLocale('kk'));
    Number::useLocale(app()->getLocale());

    expect(Number::format(12345))->toBeString();
});
