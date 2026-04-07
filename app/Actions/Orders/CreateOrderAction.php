<?php

namespace App\Actions\Orders;

use App\Enums\BonusTransactionType;
use App\Enums\OrderRecipientType;
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
     *     recipient_type?: OrderRecipientType|string|null,
     *     recipient_first_name?: string|null,
     *     recipient_last_name?: string|null,
     *     recipient_bin?: string|null,
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
            $client = $this->lockClient((int) $attributes['client_id']);
            $payload = $this->preparePayload($attributes, $source, $performedByUserId, $client);
            $reserveAmount = (int) $payload['reserved_bonus_amount'];

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
     *     recipient_type?: OrderRecipientType|string|null,
     *     recipient_first_name?: string|null,
     *     recipient_last_name?: string|null,
     *     recipient_bin?: string|null,
     *     total_bonus?: int|string|null,
     *     total_weight_grams?: int|string|null,
     *     placed_at?: mixed,
     *     created_by_user_id?: int|string|null
     * }  $attributes
     * @return array<string, mixed>
     */
    private function preparePayload(array $attributes, OrderSource $source, ?int $performedByUserId, Client $client): array
    {
        $totalBonus = (int) ($attributes['total_bonus'] ?? 0);

        return [
            'client_id' => (int) $attributes['client_id'],
            'institution_id' => (int) $attributes['institution_id'],
            'source' => $source,
            ...$this->resolveRecipientPayload($attributes, $client),
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

    /**
     * @param  array{
     *     recipient_type?: OrderRecipientType|string|null,
     *     recipient_first_name?: string|null,
     *     recipient_last_name?: string|null,
     *     recipient_bin?: string|null
     * }  $attributes
     * @return array{
     *     recipient_type: OrderRecipientType,
     *     recipient_first_name: string,
     *     recipient_last_name: string,
     *     recipient_bin: string
     * }
     */
    private function resolveRecipientPayload(array $attributes, Client $client): array
    {
        $rawRecipientType = $attributes['recipient_type'] ?? OrderRecipientType::Client;
        $recipientType = $rawRecipientType instanceof OrderRecipientType
            ? $rawRecipientType
            : OrderRecipientType::tryFrom((string) $rawRecipientType);

        if (! $recipientType) {
            throw ValidationException::withMessages([
                'data.recipient_type' => 'Некорректный тип получателя заказа.',
            ]);
        }

        if ($recipientType === OrderRecipientType::Client) {
            return [
                'recipient_type' => $recipientType,
                'recipient_first_name' => $client->first_name,
                'recipient_last_name' => $client->last_name,
                'recipient_bin' => $client->bin,
            ];
        }

        return [
            'recipient_type' => $recipientType,
            'recipient_first_name' => $this->requireRecipientValue(
                $attributes,
                'recipient_first_name',
                'Имя получателя обязательно.',
            ),
            'recipient_last_name' => $this->requireRecipientValue(
                $attributes,
                'recipient_last_name',
                'Фамилия получателя обязательна.',
            ),
            'recipient_bin' => $this->requireRecipientValue(
                $attributes,
                'recipient_bin',
                'ИИН/БИН получателя обязателен.',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function requireRecipientValue(array $attributes, string $key, string $message): string
    {
        $value = trim((string) ($attributes[$key] ?? ''));

        if (! filled($value)) {
            throw ValidationException::withMessages([
                "data.{$key}" => $message,
            ]);
        }

        return $value;
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
