<?php

use App\Enums\BonusTransactionType;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists client bonus transactions', function () {
    $client = Client::factory()->create();

    $client->bonusTransactions()->create([
        'type' => BonusTransactionType::Accrual,
        'amount' => 500,
        'balance_delta' => 500,
        'reserved_delta' => 0,
        'comment' => 'Начисление бонусов',
    ]);

    $this->actingAs($client, 'client')
        ->get(route('storefront.bonuses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/BonusHistory')
            ->has('transactions.data', 1)
        );
});
