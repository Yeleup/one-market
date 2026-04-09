<?php

namespace App\Http\Middleware;

use App\Support\Storefront\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleStorefrontInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $client = Auth::guard('client')->user();
        $cart = app(Cart::class);

        return [
            ...parent::share($request),
            'auth' => [
                'client' => $client ? [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'full_name' => $client->full_name,
                    'bin' => $client->bin,
                    'bonus_balance' => $client->bonus_balance,
                    'bonus_reserved' => $client->bonus_reserved,
                    'available_bonuses' => $client->bonus_balance - $client->bonus_reserved,
                ] : null,
            ],
            'cart' => [
                'count' => $cart->count(),
                'total_quantity' => $cart->totalQuantity(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
