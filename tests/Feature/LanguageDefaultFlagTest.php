<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps only one default language when creating another default language', function () {
    $firstLanguage = Language::factory()->default()->create();
    $secondLanguage = Language::factory()->default()->create();

    expect($secondLanguage->fresh()->is_default)->toBeTrue()
        ->and($firstLanguage->fresh()->is_default)->toBeFalse()
        ->and(Language::query()->where('is_default', true)->count())->toBe(1);
});

it('keeps only one default language when updating an existing language to default', function () {
    $defaultLanguage = Language::factory()->default()->create();
    $secondaryLanguage = Language::factory()->create();

    $secondaryLanguage->update(['is_default' => true]);

    expect($secondaryLanguage->fresh()->is_default)->toBeTrue()
        ->and($defaultLanguage->fresh()->is_default)->toBeFalse()
        ->and(Language::query()->where('is_default', true)->count())->toBe(1);
});
