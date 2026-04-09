<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderPdfController;
use App\Http\Controllers\Storefront;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/img/{path}', [ImageController::class, 'show'])
    ->where('path', '.*')
    ->name('image.show');

Route::middleware('auth')
    ->get('/orders/{order}/pdf', OrderPdfController::class)
    ->name('orders.pdf');

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
*/
Route::prefix('storefront')
    ->name('storefront.')
    ->middleware('storefront.inertia')
    ->group(function () {

        // Guest-only routes
        Route::middleware('client.guest')->group(function () {
            Route::get('login', [Storefront\Auth\LoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [Storefront\Auth\LoginController::class, 'login']);
            Route::get('register', [Storefront\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
            Route::post('register', [Storefront\Auth\RegisterController::class, 'register']);
        });

        // Logout
        Route::post('logout', Storefront\Auth\LogoutController::class)
            ->name('logout')
            ->middleware('client.auth');

        // Public catalog
        Route::post('language', Storefront\LanguageController::class)->name('language');
        Route::get('/', [Storefront\CatalogController::class, 'index'])->name('catalog');
        Route::get('products/{product}', [Storefront\CatalogController::class, 'show'])->name('products.show');

        // Cart (session-based, no auth required)
        Route::get('cart', [Storefront\CartController::class, 'index'])->name('cart.index');
        Route::post('cart', [Storefront\CartController::class, 'store'])->name('cart.store');
        Route::patch('cart/{productId}', [Storefront\CartController::class, 'update'])->name('cart.update');
        Route::delete('cart/{productId}', [Storefront\CartController::class, 'destroy'])->name('cart.destroy');

        // Authenticated client routes
        Route::middleware('client.auth')->group(function () {
            Route::get('checkout', [Storefront\CheckoutController::class, 'index'])->name('checkout');
            Route::post('checkout', [Storefront\CheckoutController::class, 'store'])->name('checkout.store');

            Route::get('dashboard', [Storefront\DashboardController::class, 'index'])->name('dashboard');

            Route::get('orders', [Storefront\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [Storefront\OrderController::class, 'show'])->name('orders.show');

            Route::get('profile', [Storefront\ProfileController::class, 'index'])->name('profile');
            Route::put('profile/password', [Storefront\ProfileController::class, 'updatePassword'])->name('profile.password');

            Route::get('bonuses', [Storefront\BonusHistoryController::class, 'index'])->name('bonuses.index');
        });
    });
