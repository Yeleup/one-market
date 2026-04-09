<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the catalog page', function () {
    $this->get(route('storefront.catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Catalog'));
});

it('only shows active products', function () {
    Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);

    $this->get(route('storefront.catalog'))
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Catalog')
            ->has('products.data', 1)
        );
});

it('passes parent categories with nested children', function (): void {
    $language = Language::factory()->default()->create(['code' => 'en']);

    $parentCategory = Category::factory()->create();
    $parentCategory->translations()->create([
        'language_id' => $language->getKey(),
        'name' => 'Snacks',
    ]);

    $childCategory = Category::factory()->create([
        'parent_id' => $parentCategory->getKey(),
    ]);
    $childCategory->translations()->create([
        'language_id' => $language->getKey(),
        'name' => 'Chips',
    ]);

    $this->get(route('storefront.catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Catalog')
            ->has('categories', 1)
            ->where('categories.0.id', $parentCategory->getKey())
            ->where('categories.0.name', 'Snacks')
            ->has('categories.0.children', 1)
            ->where('categories.0.children.0.id', $childCategory->getKey())
            ->where('categories.0.children.0.name', 'Chips')
        );
});

it('renders a single product page', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $this->get(route('storefront.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Product')
            ->has('product')
        );
});
