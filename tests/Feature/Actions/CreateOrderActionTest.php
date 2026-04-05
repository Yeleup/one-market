<?php

use App\Actions\Orders\CreateOrderAction;
use App\Enums\BonusTransactionType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates an admin order and reserves client bonuses', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 100,
    ]);
    $institution = Institution::factory()->create();

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'total_bonus' => 200,
        'total_weight_grams' => 1500,
    ], OrderSource::Admin, $admin->getKey());

    expect($order->status)->toBe(OrderStatus::New)
        ->and($order->source)->toBe(OrderSource::Admin)
        ->and($order->reserved_bonus_amount)->toBe(200)
        ->and($order->created_by_user_id)->toBe($admin->getKey());

    expect($client->fresh()->bonus_balance)->toBe(500)
        ->and($client->fresh()->bonus_reserved)->toBe(300);

    $transaction = $order->bonusTransactions()->sole();

    expect($transaction->type)->toBe(BonusTransactionType::Reserve)
        ->and($transaction->amount)->toBe(200)
        ->and($transaction->balance_delta)->toBe(0)
        ->and($transaction->reserved_delta)->toBe(200)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());

    $statusHistory = $order->statusHistories()->sole();

    expect($statusHistory->from_status)->toBeNull()
        ->and($statusHistory->to_status)->toBe(OrderStatus::New)
        ->and($statusHistory->changed_by_user_id)->toBe($admin->getKey());
});

it('does not create an order when the client lacks available bonuses', function () {
    $client = Client::factory()->create([
        'bonus_balance' => 200,
        'bonus_reserved' => 150,
    ]);
    $institution = Institution::factory()->create();

    expect(fn () => app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'total_bonus' => 100,
        'total_weight_grams' => 500,
    ]))->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0)
        ->and($client->fresh()->bonus_reserved)->toBe(150);
});
