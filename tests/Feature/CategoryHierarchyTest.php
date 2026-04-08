<?php

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores parent categories and nulls the parent_id when the parent is deleted', function (): void {
    $parentCategory = Category::factory()->create();
    $subcategory = Category::factory()
        ->for($parentCategory, 'parent')
        ->create();

    expect($subcategory->parent?->is($parentCategory))->toBeTrue();
    expect($parentCategory->subcategories()->pluck('id')->all())
        ->toContain($subcategory->getKey());

    $parentCategory->delete();

    expect($subcategory->fresh()?->parent_id)->toBeNull();
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
