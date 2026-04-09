<?php

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out the authenticated client', function () {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client')
        ->post(route('storefront.logout'))
        ->assertRedirect(route('storefront.login'));

    $this->assertGuest('client');
});
