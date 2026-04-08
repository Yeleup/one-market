<?php

use App\Http\Controllers\OrderPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')
    ->get('/orders/{order}/pdf', OrderPdfController::class)
    ->name('orders.pdf');
