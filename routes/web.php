<?php

use App\Http\Controllers\OrderPdfController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::middleware('auth')
    ->get('/orders/{order}/pdf', OrderPdfController::class)
    ->name('orders.pdf');
