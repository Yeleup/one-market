<?php

use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists only the authenticated client orders', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $institution = Institution::factory()->create();

    Order::factory()->for($client)->for($institution)->create();
    Order::factory()->for($client)->for($institution)->create();
    Order::factory()->for($otherClient)->for($institution)->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Orders')
            ->has('orders.data', 2)
        );
});

it('shows order detail', function () {
    $client = Client::factory()->create();
    $institution = Institution::factory()->create();
    $order = Order::factory()->for($client)->for($institution)->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/OrderDetail')
            ->has('order')
        );
});

it('forbids viewing another client order', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $institution = Institution::factory()->create();
    $order = Order::factory()->for($otherClient)->for($institution)->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.orders.show', $order))
        ->assertNotFound();
});

it('returns an image route url for order items', function (): void {
    Storage::fake('public');

    $imagePath = 'products/order-image.png';

    Storage::disk('public')->put(
        $imagePath,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR42mNk+M/wHwAEAQH/cetH5QAAAABJRU5ErkJggg==')
    );

    $client = Client::factory()->create();
    $institution = Institution::factory()->create();
    $product = Product::factory()->create(['image' => $imagePath]);
    $order = Order::factory()->for($client)->for($institution)->create();

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->slug,
        'product_image' => $imagePath,
        'price_bonus' => 100,
        'weight_grams' => 200,
        'quantity' => 1,
        'line_total_bonus' => 100,
        'line_total_weight_grams' => 200,
    ]);

    $response = $this->actingAs($client, 'client')
        ->get(route('storefront.orders.show', $order));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('order.items', 1)
            ->where('order.items.0.product_image', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=160') && str_contains($url, 'fm=webp'))
        );
});
