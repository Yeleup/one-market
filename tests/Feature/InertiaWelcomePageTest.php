<?php

use Inertia\Testing\AssertableInertia;

test('the root url renders the storefront catalog page', function () {
    config()->set('session.driver', 'array');

    $response = $this->get('/');

    $response
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Storefront/Catalog'));
});

test('the inertia app loads css through the js vite entrypoint', function () {
    expect(file_get_contents(resource_path('js/app.js')))
        ->toContain("import '../css/app.css';");

    expect(file_get_contents(resource_path('views/app.blade.php')))
        ->toContain("@vite('resources/js/app.js')")
        ->not->toContain('resources/css/app.css');
});
