<?php

use App\Actions\Orders\CreateOrderAction;
use App\Enums\BonusTransactionType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RecipientType;
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
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
        'bin' => '123456789012',
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
        ->and($order->recipient_type)->toBe(RecipientType::Client)
        ->and($order->recipient_first_name)->toBe('Ivan')
        ->and($order->recipient_last_name)->toBe('Petrov')
        ->and($order->recipient_bin)->toBe('123456789012')
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

it('creates an admin order for another recipient and stores a recipient snapshot', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'first_name' => 'Client',
        'last_name' => 'Owner',
        'bin' => '999999999999',
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $institution = Institution::factory()->create();

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'recipient_type' => RecipientType::Other,
        'recipient_first_name' => 'Aruzhan',
        'recipient_last_name' => 'Sadykova',
        'recipient_bin' => '870101300123',
        'total_bonus' => 120,
        'total_weight_grams' => 800,
    ], OrderSource::Admin, $admin->getKey());

    expect($order->recipient_type)->toBe(RecipientType::Other)
        ->and($order->recipient_first_name)->toBe('Aruzhan')
        ->and($order->recipient_last_name)->toBe('Sadykova')
        ->and($order->recipient_bin)->toBe('870101300123')
        ->and($order->client_id)->toBe($client->getKey());
});

it('uses the saved client recipient when the client registers another recipient', function () {
    $admin = User::factory()->create();
    $client = Client::factory()->create([
        'first_name' => 'Client',
        'last_name' => 'Owner',
        'bin' => '999999999999',
        'recipient_type' => RecipientType::Other,
        'recipient_first_name' => 'Aruzhan',
        'recipient_last_name' => 'Sadykova',
        'recipient_bin' => '870101300123',
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $institution = Institution::factory()->create();

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'total_bonus' => 120,
        'total_weight_grams' => 800,
    ], OrderSource::Admin, $admin->getKey());

    expect($order->recipient_type)->toBe(RecipientType::Other)
        ->and($order->recipient_first_name)->toBe('Aruzhan')
        ->and($order->recipient_last_name)->toBe('Sadykova')
        ->and($order->recipient_bin)->toBe('870101300123');
});

it('uses the client institution when it is not provided explicitly', function () {
    $admin = User::factory()->create();
    $institution = Institution::factory()->create();
    $client = Client::factory()->create([
        'institution_id' => $institution->getKey(),
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);

    $order = app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'total_bonus' => 120,
        'total_weight_grams' => 800,
    ], OrderSource::Admin, $admin->getKey());

    expect($order->institution_id)->toBe($institution->getKey());
});

it('rejects creating an order for another recipient without recipient details', function () {
    $client = Client::factory()->create([
        'bonus_balance' => 500,
        'bonus_reserved' => 0,
    ]);
    $institution = Institution::factory()->create();

    expect(fn () => app(CreateOrderAction::class)->handle([
        'client_id' => $client->getKey(),
        'institution_id' => $institution->getKey(),
        'recipient_type' => RecipientType::Other,
        'recipient_first_name' => '',
        'total_bonus' => 100,
        'total_weight_grams' => 500,
    ]))->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0)
        ->and($client->fresh()->bonus_reserved)->toBe(0);
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
