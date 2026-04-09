<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        return Inertia::render('Storefront/Dashboard', [
            'client' => [
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'bin' => $client->bin,
                'bonus_balance' => $client->bonus_balance,
                'bonus_reserved' => $client->bonus_reserved,
                'available_bonuses' => $client->bonus_balance - $client->bonus_reserved,
            ],
            'recentOrders' => $client->orders()
                ->latest('placed_at')
                ->limit(5)
                ->get()
                ->map(fn ($order) => [
                    'id' => $order->id,
                    'url' => route('storefront.orders.show', $order, false),
                    'status' => $order->status->value,
                    'total_bonus' => $order->total_bonus,
                    'placed_at' => $order->placed_at?->format('d.m.Y H:i'),
                ]),
            'recentTransactions' => $client->bonusTransactions()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($tx) => [
                    'id' => $tx->id,
                    'type' => $tx->type->value,
                    'amount' => $tx->amount,
                    'balance_delta' => $tx->balance_delta,
                    'reserved_delta' => $tx->reserved_delta,
                    'comment' => $tx->comment,
                    'created_at' => $tx->created_at?->format('d.m.Y H:i'),
                ]),
        ]);
    }
}
