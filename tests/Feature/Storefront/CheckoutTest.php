<?php

use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Product;
use App\Support\Storefront\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view checkout', function () {
    $this->get(route('storefront.checkout'))
        ->assertRedirect(route('storefront.login'));
});

it('renders checkout page', function () {
    $client = Client::factory()->create(['bonus_balance' => 5000]);
    Institution::factory()->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.checkout'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Checkout'));
});

it('places an order successfully', function () {
    $institution = Institution::factory()->create(['max_weight_grams' => 10000]);
    $client = Client::factory()->create([
        'bonus_balance' => 5000,
        'bonus_reserved' => 0,
        'institution_id' => $institution->id,
    ]);
    $product = Product::factory()->create([
        'bonus_price' => 100,
        'weight_grams' => 500,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    // Add product to cart via session
    $this->actingAs($client, 'client')
        ->withSession(['storefront_cart' => [$product->id => 2]])
        ->post(route('storefront.checkout.store'), [
            'institution_id' => $institution->id,
        ])
        ->assertRedirect();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order->total_bonus)->toBe(200)
        ->and($order->total_weight_grams)->toBe(1000)
        ->and($order->items)->toHaveCount(1);

    // Stock decremented
    expect($product->fresh()->stock_quantity)->toBe(8);

    // Bonuses reserved
    expect($client->fresh()->bonus_reserved)->toBe(200);
});

it('rejects order when insufficient bonuses', function () {
    $institution = Institution::factory()->create(['max_weight_grams' => 10000]);
    $client = Client::factory()->create([
        'bonus_balance' => 50,
        'bonus_reserved' => 0,
    ]);
    $product = Product::factory()->create([
        'bonus_price' => 100,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($client, 'client')
        ->withSession(['storefront_cart' => [$product->id => 1]])
        ->post(route('storefront.checkout.store'), [
            'institution_id' => $institution->id,
        ])
        ->assertSessionHasErrors();

    expect(Order::count())->toBe(0);
});

it('rejects order when institution weight limit exceeded', function () {
    $institution = Institution::factory()->create(['max_weight_grams' => 500]);
    $client = Client::factory()->create([
        'bonus_balance' => 50000,
        'bonus_reserved' => 0,
    ]);
    $product = Product::factory()->create([
        'bonus_price' => 100,
        'weight_grams' => 1000,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($client, 'client')
        ->withSession(['storefront_cart' => [$product->id => 1]])
        ->post(route('storefront.checkout.store'), [
            'institution_id' => $institution->id,
        ])
        ->assertSessionHasErrors();

    expect(Order::count())->toBe(0);
});

it('rejects order with empty cart', function () {
    $institution = Institution::factory()->create();
    $client = Client::factory()->create(['bonus_balance' => 5000]);

    $this->actingAs($client, 'client')
        ->post(route('storefront.checkout.store'), [
            'institution_id' => $institution->id,
        ])
        ->assertSessionHasErrors();

    expect(Order::count())->toBe(0);
});
