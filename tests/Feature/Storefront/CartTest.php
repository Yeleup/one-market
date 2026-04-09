<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the cart page', function () {
    $this->get(route('storefront.cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Cart'));
});

it('adds a product to cart', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

    $this->post(route('storefront.cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $this->get(route('storefront.cart.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Cart')
            ->has('items', 1)
        );
});

it('rejects adding inactive product', function () {
    $product = Product::factory()->create(['is_active' => false]);

    $this->post(route('storefront.cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertRedirect();

    // Cart should show 0 items since inactive product is filtered out
    $this->get(route('storefront.cart.index'))
        ->assertInertia(fn ($page) => $page->has('items', 0));
});

it('updates cart item quantity', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

    $this->post(route('storefront.cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->patch(route('storefront.cart.update', $product->id), [
        'quantity' => 5,
    ])->assertRedirect()
        ->assertSessionHas('success');
});

it('removes item when quantity set to zero', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

    $this->post(route('storefront.cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->patch(route('storefront.cart.update', $product->id), [
        'quantity' => 0,
    ])->assertRedirect();

    $this->get(route('storefront.cart.index'))
        ->assertInertia(fn ($page) => $page->has('items', 0));
});

it('removes a product from cart', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

    $this->post(route('storefront.cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->delete(route('storefront.cart.destroy', $product->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->get(route('storefront.cart.index'))
        ->assertInertia(fn ($page) => $page->has('items', 0));
});
