<?php

use App\Models\Category;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('selects the localized name for the current locale', function (): void {
    $defaultLanguage = Language::factory()->default()->create(['code' => 'en']);
    $currentLanguage = Language::factory()->create(['code' => 'kk']);

    app()->setLocale('kk');

    $category = Category::factory()->create();
    $category->translations()->createMany([
        ['language_id' => $defaultLanguage->getKey(), 'name' => 'Books'],
        ['language_id' => $currentLanguage->getKey(), 'name' => 'Kitaptar'],
    ]);

    $localizedCategory = Category::query()
        ->withLocalizedName()
        ->findOrFail($category->getKey());

    expect($localizedCategory->localized_name)->toBe('Kitaptar');
});

it('falls back to the default language when the current locale is missing', function (): void {
    $defaultLanguage = Language::factory()->default()->create(['code' => 'en']);
    Language::factory()->create(['code' => 'kk']);

    app()->setLocale('kk');

    $category = Category::factory()->create();
    $category->translations()->create([
        'language_id' => $defaultLanguage->getKey(),
        'name' => 'Books',
    ]);

    $localizedCategory = Category::query()
        ->withLocalizedName()
        ->findOrFail($category->getKey());

    expect($localizedCategory->localized_name)->toBe('Books');
});

it('falls back to the first available translation when preferred languages are unavailable', function (): void {
    Language::factory()->default()->create(['code' => 'en']);
    Language::factory()->create(['code' => 'kk']);
    $fallbackLanguage = Language::factory()->create(['code' => 'ru']);

    app()->setLocale('kk');

    $category = Category::factory()->create();
    $category->translations()->create([
        'language_id' => $fallbackLanguage->getKey(),
        'name' => 'Knigi',
    ]);

    $localizedCategory = Category::query()
        ->withLocalizedName()
        ->findOrFail($category->getKey());

    expect($localizedCategory->localized_name)->toBe('Knigi');
});

it('searches localized names across available translations', function (): void {
    Language::factory()->default()->create(['code' => 'en']);
    $currentLanguage = Language::factory()->create(['code' => 'kk']);

    app()->setLocale('kk');

    $category = Category::factory()->create();
    $category->translations()->createMany([
        ['language_id' => $currentLanguage->getKey(), 'name' => 'Kitaptar'],
        ['language_id' => Language::factory()->create(['code' => 'ru'])->getKey(), 'name' => 'Knigi'],
    ]);

    $matchedIds = Category::query()
        ->searchLocalizedName('Knigi')
        ->pluck('id');

    expect($matchedIds)->toContain($category->getKey());
});
