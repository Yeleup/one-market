<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login page', function () {
    $this->get(route('storefront.login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Auth/Login'));
});

it('logs in a client with valid bin and password', function () {
    $client = Client::factory()->create([
        'bin' => '123456789012',
        'password' => 'password123',
        'is_active' => true,
    ]);

    $this->post(route('storefront.login'), [
        'bin' => '123456789012',
        'password' => 'password123',
    ])->assertRedirect(route('storefront.dashboard'));

    $this->assertAuthenticatedAs($client, 'client');
});

it('rejects invalid credentials', function () {
    Client::factory()->create([
        'bin' => '123456789012',
        'password' => 'password123',
    ]);

    $this->post(route('storefront.login'), [
        'bin' => '123456789012',
        'password' => 'wrongpassword',
    ])->assertSessionHasErrors('bin');

    $this->assertGuest('client');
});

it('rejects inactive client login', function () {
    Client::factory()->create([
        'bin' => '123456789012',
        'password' => 'password123',
        'is_active' => false,
    ]);

    $this->post(route('storefront.login'), [
        'bin' => '123456789012',
        'password' => 'password123',
    ])->assertSessionHasErrors('bin');

    $this->assertGuest('client');
});

it('redirects authenticated client away from login page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.login'))
        ->assertRedirect(route('storefront.dashboard'));
});
