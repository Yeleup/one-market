<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BonusHistoryController extends Controller
{
    public function index(): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $transactions = $client->bonusTransactions()
            ->latest()
            ->paginate(15);

        return Inertia::render('Storefront/BonusHistory', [
            'transactions' => $transactions->through(fn ($tx) => [
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
