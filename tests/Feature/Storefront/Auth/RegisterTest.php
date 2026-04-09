<?php

use App\Models\Client;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the registration page', function () {
    $this->get(route('storefront.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Auth/Register'));
});

it('registers a new client', function () {
    $institution = Institution::factory()->create();

    $this->post(route('storefront.register'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'bin' => '123456789012',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'institution_id' => $institution->id,
    ])->assertRedirect(route('storefront.dashboard'));

    expect(Client::where('bin', '123456789012')->exists())->toBeTrue();
    $this->assertAuthenticated('client');
});

it('validates required fields', function () {
    $this->post(route('storefront.register'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'bin', 'password']);
});

it('rejects duplicate bin', function () {
    Client::factory()->create(['bin' => '123456789012']);

    $this->post(route('storefront.register'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'bin' => '123456789012',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('bin');
});
