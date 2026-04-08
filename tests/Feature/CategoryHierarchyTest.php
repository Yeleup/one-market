<?php

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores parent categories and cascades deletion to subcategories', function (): void {
    $parentCategory = Category::factory()->create();
    $subcategory = Category::factory()
        ->for($parentCategory, 'parent')
        ->create();

    expect($subcategory->parent?->is($parentCategory))->toBeTrue();
    expect($parentCategory->subcategories()->pluck('id')->all())
        ->toContain($subcategory->getKey());

    $parentCategory->delete();

    $this->assertModelMissing($subcategory);
});

it('loads the localized parent through the category resource query', function (): void {
    $language = Language::factory()->default()->create(['code' => 'en']);
    $parentCategory = Category::factory()->create();
    $childCategory = Category::factory()
        ->for($parentCategory, 'parent')
        ->create();

    $parentCategory->translations()->create([
        'language_id' => $language->getKey(),
        'name' => 'Books',
    ]);

    $category = CategoryResource::getEloquentQuery()
        ->findOrFail($childCategory->getKey());

    expect($category->relationLoaded('parent'))->toBeTrue();
    expect($category->parent?->localized_name)->toBe('Books');
});

it('uses sort_order for categories with a default value of zero', function (): void {
    $laterCategory = Category::factory()->create(['sort_order' => 20]);
    $earlierCategory = Category::factory()->create(['sort_order' => 10]);
    $defaultOrderCategory = Category::factory()->create();

    expect($defaultOrderCategory->sort_order)->toBe(0);
    expect($defaultOrderCategory->getOrderKeyName())->toBe('sort_order');

    $orderedIds = CategoryResource::getEloquentQuery()
        ->pluck('categories.id')
        ->all();

    expect($orderedIds)->toBe([
        $defaultOrderCategory->getKey(),
        $earlierCategory->getKey(),
        $laterCategory->getKey(),
    ]);
});
