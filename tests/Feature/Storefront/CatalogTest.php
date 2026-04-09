<?php

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

it('renders a single product page', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $this->get(route('storefront.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Product')
            ->has('product')
        );
});
