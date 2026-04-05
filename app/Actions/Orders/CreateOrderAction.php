<?php

namespace App\Actions\Orders;

use App\Enums\BonusTransactionType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    /**
     * @param  array{
     *     client_id: int|string,
     *     institution_id: int|string,
     *     total_bonus?: int|string|null,
     *     total_weight_grams?: int|string|null,
     *     placed_at?: mixed,
     *     created_by_user_id?: int|string|null
     * }  $attributes
     */
    public function handle(array $attributes, OrderSource $source = OrderSource::Admin, ?int $performedByUserId = null): Order
    {
        /** @var Order $order */
        $order = DB::transaction(function () use ($attributes, $performedByUserId, $source): Order {
            $payload = $this->preparePayload($attributes, $source, $performedByUserId);
            $reserveAmount = (int) $payload['reserved_bonus_amount'];
            $client = $this->lockClient((int) $payload['client_id']);

            $this->assertEnoughAvailableBonuses($client, $reserveAmount);

            $order = Order::query()->create($payload);

            $this->reserveClientBonuses($client, $order, $reserveAmount, $performedByUserId);
            $this->createInitialStatusHistory($order, $performedByUserId);

            return $order;
        });

        return $order;
    }

    /**
     * @param  array{
     *     client_id: int|string,
     *     institution_id: int|string,
     *     total_bonus?: int|string|null,
     *     total_weight_grams?: int|string|null,
     *     placed_at?: mixed,
     *     created_by_user_id?: int|string|null
     * }  $attributes
     * @return array<string, mixed>
     */
    private function preparePayload(array $attributes, OrderSource $source, ?int $performedByUserId): array
    {
        $totalBonus = (int) ($attributes['total_bonus'] ?? 0);

        return [
            'client_id' => (int) $attributes['client_id'],
            'institution_id' => (int) $attributes['institution_id'],
            'source' => $source,
            'status' => OrderStatus::New,
            'total_bonus' => $totalBonus,
            'total_weight_grams' => (int) ($attributes['total_weight_grams'] ?? 0),
            'reserved_bonus_amount' => $totalBonus,
            'placed_at' => $attributes['placed_at'] ?? now(),
            'status_changed_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'created_by_user_id' => $source === OrderSource::Admin ? $performedByUserId : null,
        ];
    }

    private function lockClient(int $clientId): Client
    {
        /** @var Client $client */
        $client = Client::query()
            ->lockForUpdate()
            ->findOrFail($clientId);

        return $client;
    }

    private function assertEnoughAvailableBonuses(Client $client, int $reserveAmount): void
    {
        $availableBonuses = (int) $client->bonus_balance - (int) $client->bonus_reserved;

        if ($reserveAmount > $availableBonuses) {
            throw ValidationException::withMessages([
                'data.client_id' => 'Недостаточно доступных бонусов для оформления заказа.',
            ]);
        }
    }

    private function reserveClientBonuses(Client $client, Order $order, int $amount, ?int $performedByUserId): void
    {
        if ($amount <= 0) {
            return;
        }

        $client->update([
            'bonus_reserved' => $client->bonus_reserved + $amount,
        ]);

        $client->bonusTransactions()->create([
            'order_id' => $order->getKey(),
            'performed_by_user_id' => $performedByUserId,
            'type' => BonusTransactionType::Reserve,
            'amount' => $amount,
            'balance_delta' => 0,
            'reserved_delta' => $amount,
            'comment' => 'Резерв бонусов при создании заказа.',
        ]);
    }

    private function createInitialStatusHistory(Order $order, ?int $performedByUserId): void
    {
        $order->statusHistories()->create([
            'from_status' => null,
            'to_status' => OrderStatus::New,
            'changed_by_user_id' => $performedByUserId,
            'comment' => 'Заказ создан.',
        ]);
    }
}
