<?php

use App\Actions\Orders\SyncOrderTotalsAction;
use App\Enums\BonusTransactionType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('synchronizes order totals and increases the client reserve from items', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'status' => OrderStatus::New,
        'total_bonus' => 0,
        'total_weight_grams' => 0,
        'reserved_bonus_amount' => 0,
    ]);
    $product = Product::factory()->create();

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'product_name' => 'Product A',
        'product_image' => null,
        'price_bonus' => 120,
        'weight_grams' => 300,
        'quantity' => 2,
        'line_total_bonus' => 240,
        'line_total_weight_grams' => 600,
    ]);

    $syncedOrder = app(SyncOrderTotalsAction::class)->handle($order, $admin->getKey());

    expect($syncedOrder->total_bonus)->toBe(240)
        ->and($syncedOrder->total_weight_grams)->toBe(600)
        ->and($syncedOrder->reserved_bonus_amount)->toBe(240);

    expect($client->fresh()->bonus_reserved)->toBe(240);

    $transaction = $order->bonusTransactions()->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(BonusTransactionType::Reserve)
        ->and($transaction->amount)->toBe(240)
        ->and($transaction->reserved_delta)->toBe(240)
        ->and($transaction->performed_by_user_id)->toBe($admin->getKey());
});

it('returns excess reserve when order items total decreases', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 300,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'status' => OrderStatus::Processing,
        'total_bonus' => 300,
        'total_weight_grams' => 900,
        'reserved_bonus_amount' => 300,
    ]);
    $product = Product::factory()->create();

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'product_name' => 'Product A',
        'product_image' => null,
        'price_bonus' => 100,
        'weight_grams' => 200,
        'quantity' => 2,
        'line_total_bonus' => 200,
        'line_total_weight_grams' => 400,
    ]);

    $syncedOrder = app(SyncOrderTotalsAction::class)->handle($order, $admin->getKey());

    expect($syncedOrder->total_bonus)->toBe(200)
        ->and($syncedOrder->reserved_bonus_amount)->toBe(200);

    expect($client->fresh()->bonus_reserved)->toBe(200);

    $transaction = $order->bonusTransactions()->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(BonusTransactionType::ReserveReturn)
        ->and($transaction->amount)->toBe(100)
        ->and($transaction->reserved_delta)->toBe(-100);
});

it('fails when updated order items require more bonuses than the client can reserve', function () {
    $client = Client::factory()->create([
        'bonus_balance' => 120,
        'bonus_reserved' => 50,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'status' => OrderStatus::New,
        'total_bonus' => 50,
        'total_weight_grams' => 100,
        'reserved_bonus_amount' => 50,
    ]);
    $product = Product::factory()->create();

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'product_name' => 'Product A',
        'product_image' => null,
        'price_bonus' => 130,
        'weight_grams' => 100,
        'quantity' => 1,
        'line_total_bonus' => 130,
        'line_total_weight_grams' => 100,
    ]);

    expect(fn () => app(SyncOrderTotalsAction::class)->handle($order))
        ->toThrow(RuntimeException::class);
});

it('allows increasing reserve above available bonuses for admin orders', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 120,
        'bonus_reserved' => 50,
    ]);
    $order = Order::factory()->create([
        'client_id' => $client->getKey(),
        'source' => OrderSource::Admin,
        'status' => OrderStatus::New,
        'total_bonus' => 50,
        'total_weight_grams' => 100,
        'reserved_bonus_amount' => 50,
    ]);
    $product = Product::factory()->create();

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'product_name' => 'Product A',
        'product_image' => null,
        'price_bonus' => 130,
        'weight_grams' => 100,
        'quantity' => 1,
        'line_total_bonus' => 130,
        'line_total_weight_grams' => 100,
    ]);

    $syncedOrder = app(SyncOrderTotalsAction::class)->handle($order, $admin->getKey());

    expect($syncedOrder->total_bonus)->toBe(130)
        ->and($syncedOrder->reserved_bonus_amount)->toBe(130);

    expect($client->fresh()->bonus_balance)->toBe(120)
        ->and($client->fresh()->bonus_reserved)->toBe(130);
});
