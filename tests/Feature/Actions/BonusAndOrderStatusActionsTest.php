<?php

use App\Actions\Bonuses\AccrueBonusesAction;
use App\Actions\Bonuses\ManualDebitBonusesAction;
use App\Actions\Orders\ChangeOrderStatusAction;
use App\Enums\BonusTransactionType;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accrues bonuses and records the transaction', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 100,
        'bonus_reserved' => 0,
    ]);

    app(AccrueBonusesAction::class)->handle($client, 75, $admin->getKey());

    expect($client->fresh()->bonus_balance)->toBe(175);

    $transaction = $client->bonusTransactions()->sole();

    expect($transaction->type)->toBe(BonusTransactionType::Accrual)
        ->and($transaction->amount)->toBe(75)
        ->and($transaction->balance_delta)->toBe(75)
        ->and($transaction->reserved_delta)->toBe(0)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());
});

it('manually debits available bonuses and records the transaction', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 400,
        'bonus_reserved' => 150,
    ]);

    app(ManualDebitBonusesAction::class)->handle($client, 100, $admin->getKey());

    expect($client->fresh()->bonus_balance)->toBe(300)
        ->and($client->fresh()->bonus_reserved)->toBe(150);

    $transaction = $client->bonusTransactions()->sole();

    expect($transaction->type)->toBe(BonusTransactionType::ManualDebit)
        ->and($transaction->amount)->toBe(100)
        ->and($transaction->balance_delta)->toBe(-100)
        ->and($transaction->reserved_delta)->toBe(0)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());
});

it('rejects manual debit when it exceeds the available balance', function () {
    $client = Client::factory()->create([
        'bonus_balance' => 200,
        'bonus_reserved' => 180,
    ]);

    expect(fn () => app(ManualDebitBonusesAction::class)->handle($client, 50))
        ->toThrow(RuntimeException::class);

    expect($client->fresh()->bonus_balance)->toBe(200)
        ->and($client->bonusTransactions()->count())->toBe(0);
});

it('writes off reserved bonuses when an order is delivered', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 200,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'status' => OrderStatus::Processing,
        'reserved_bonus_amount' => 200,
    ]);

    $updatedOrder = app(ChangeOrderStatusAction::class)->handle($order, OrderStatus::Delivered, $admin->getKey());

    expect($updatedOrder->status)->toBe(OrderStatus::Delivered)
        ->and($updatedOrder->delivered_at)->not->toBeNull();

    expect($client->fresh()->bonus_balance)->toBe(300)
        ->and($client->fresh()->bonus_reserved)->toBe(0);

    $transaction = $updatedOrder->bonusTransactions()->sole();

    expect($transaction->type)->toBe(BonusTransactionType::WriteOff)
        ->and($transaction->amount)->toBe(200)
        ->and($transaction->balance_delta)->toBe(-200)
        ->and($transaction->reserved_delta)->toBe(-200)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());
});

it('returns reserved bonuses when an order is cancelled', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 200,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'status' => OrderStatus::Processing,
        'reserved_bonus_amount' => 200,
    ]);

    $updatedOrder = app(ChangeOrderStatusAction::class)->handle($order, OrderStatus::Cancelled, $admin->getKey());

    expect($updatedOrder->status)->toBe(OrderStatus::Cancelled)
        ->and($updatedOrder->cancelled_at)->not->toBeNull();

    expect($client->fresh()->bonus_balance)->toBe(500)
        ->and($client->fresh()->bonus_reserved)->toBe(0);

    $transaction = $updatedOrder->bonusTransactions()->sole();

    expect($transaction->type)->toBe(BonusTransactionType::ReserveReturn)
        ->and($transaction->amount)->toBe(200)
        ->and($transaction->balance_delta)->toBe(0)
        ->and($transaction->reserved_delta)->toBe(-200)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());
});
