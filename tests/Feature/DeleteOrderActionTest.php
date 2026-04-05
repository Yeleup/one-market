<?php

use App\Actions\Orders\ChangeOrderStatusAction;
use App\Actions\Orders\CreateOrderAction;
use App\Actions\Orders\DeleteOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\BonusTransaction;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes a cancelled order with related items histories and bonus transactions', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $institution = Institution::factory()->create();
    $product = Product::factory()->create();

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'total_bonus' => 200,
        'total_weight_grams' => 500,
    ], OrderSource::Admin, $admin->getKey());

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'product_name' => 'Product A',
        'product_image' => null,
        'price_bonus' => 200,
        'weight_grams' => 500,
        'quantity' => 1,
        'line_total_bonus' => 200,
        'line_total_weight_grams' => 500,
    ]);

    $order = app(ChangeOrderStatusAction::class)->handle($order, OrderStatus::Cancelled, $admin->getKey());

    app(DeleteOrderAction::class)->handle($order);

    $this->assertModelMissing($order);

    expect(OrderItem::count())->toBe(0)
        ->and(OrderStatusHistory::count())->toBe(0)
        ->and(BonusTransaction::count())->toBe(0)
        ->and($client->fresh()->bonus_balance)->toBe(500)
        ->and($client->fresh()->bonus_reserved)->toBe(0);
});

it('rejects deleting an order until it is cancelled', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $institution = Institution::factory()->create();

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'total_bonus' => 150,
        'total_weight_grams' => 400,
    ], OrderSource::Admin, $admin->getKey());

    expect(fn () => app(DeleteOrderAction::class)->handle($order))
        ->toThrow(RuntimeException::class, 'Удаление доступно только для отменённого заказа.');

    $this->assertModelExists($order);

    expect(Order::count())->toBe(1)
        ->and(OrderStatusHistory::count())->toBe(1)
        ->and(BonusTransaction::count())->toBe(1)
        ->and($client->fresh()->bonus_reserved)->toBe(150);
});
