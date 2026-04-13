<?php

use App\Http\Middleware\SetAdminLocaleFromDefaultLanguage;
use Illuminate\Support\Facades\Route;

it('applies the admin locale middleware to the order pdf route', function (): void {
    $route = Route::getRoutes()->getByName('orders.pdf');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain(SetAdminLocaleFromDefaultLanguage::class);
});
