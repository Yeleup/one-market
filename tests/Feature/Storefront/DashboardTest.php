<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->get(route('storefront.dashboard'))
        ->assertRedirect(route('storefront.login'));
});

it('renders dashboard with client data', function () {
    $client = Client::factory()->create([
        'bonus_balance' => 5000,
        'bonus_reserved' => 1000,
    ]);

    $this->actingAs($client, 'client')
        ->get(route('storefront.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Dashboard')
            ->has('client')
            ->where('client.bonus_balance', 5000)
            ->where('client.bonus_reserved', 1000)
            ->where('client.available_bonuses', 4000)
        );
});
