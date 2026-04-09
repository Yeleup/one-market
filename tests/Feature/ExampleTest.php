<?php

use Inertia\Testing\AssertableInertia;

test('the application renders the storefront on the root url', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Storefront/Catalog'));
});
