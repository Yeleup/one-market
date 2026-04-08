<?php

use Inertia\Testing\AssertableInertia;

test('the welcome page renders the inertia home page', function () {
    config()->set('session.driver', 'array');

    $response = $this->get('/');

    $response
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Home', false));
});
