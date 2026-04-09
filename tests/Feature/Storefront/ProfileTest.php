<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('renders profile page', function () {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client')
        ->get(route('storefront.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Profile'));
});

it('changes password with valid current password', function () {
    $client = Client::factory()->create([
        'password' => 'oldpassword',
    ]);

    $this->actingAs($client, 'client')
        ->put(route('storefront.profile.password'), [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Hash::check('newpassword123', $client->fresh()->password))->toBeTrue();
});

it('rejects incorrect current password', function () {
    $client = Client::factory()->create([
        'password' => 'oldpassword',
    ]);

    $this->actingAs($client, 'client')
        ->put(route('storefront.profile.password'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertSessionHasErrors('current_password');
});
