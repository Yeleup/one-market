<?php

use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
