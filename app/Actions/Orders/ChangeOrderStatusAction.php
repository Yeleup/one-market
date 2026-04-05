<?php

namespace App\Actions\Orders;

use App\Enums\BonusTransactionType;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeOrderStatusAction
{
    public function handle(Order $order, OrderStatus $targetStatus, ?int $changedByUserId = null, ?string $comment = null): Order
    {
        /** @var Order $updatedOrder */
        $updatedOrder = DB::transaction(function () use ($order, $targetStatus, $changedByUserId, $comment): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            $fromStatus = $lockedOrder->status;

            $this->assertTransitionAllowed($fromStatus, $targetStatus);
            $this->applyBonusSideEffects($lockedOrder, $targetStatus, $changedByUserId);

            $lockedOrder->update($this->getOrderStatusPayload($targetStatus));
            $this->createStatusHistory($lockedOrder, $fromStatus, $targetStatus, $changedByUserId, $comment);

            return $lockedOrder->fresh();
        });

        return $updatedOrder;
    }

    private function assertTransitionAllowed(OrderStatus $currentStatus, OrderStatus $targetStatus): void
    {
        $isAllowed = match ($targetStatus) {
            OrderStatus::Processing => $currentStatus === OrderStatus::New,
            OrderStatus::ReadyForDelivery => $currentStatus === OrderStatus::Processing,
            OrderStatus::Delivered => $currentStatus === OrderStatus::ReadyForDelivery,
            OrderStatus::Cancelled => ! in_array($currentStatus, [OrderStatus::Delivered, OrderStatus::Cancelled], true),
            default => false,
        };

        if (! $isAllowed) {
            throw new RuntimeException('Недопустимый переход статуса заказа.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrderStatusPayload(OrderStatus $targetStatus): array
    {
        $payload = [
            'status' => $targetStatus,
            'status_changed_at' => now(),
        ];

        if ($targetStatus === OrderStatus::Delivered) {
            $payload['delivered_at'] = now();
        }

        if ($targetStatus === OrderStatus::Cancelled) {
            $payload['cancelled_at'] = now();
        }

        return $payload;
    }

    private function applyBonusSideEffects(Order $order, OrderStatus $targetStatus, ?int $changedByUserId): void
    {
        if (! in_array($targetStatus, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
            return;
        }

        $amount = (int) $order->reserved_bonus_amount;

        if ($amount <= 0) {
            return;
        }

        /** @var Client $client */
        $client = Client::query()
            ->lockForUpdate()
            ->findOrFail($order->client_id);

        if ($client->bonus_reserved < $amount) {
            throw new RuntimeException('У клиента недостаточно зарезервированных бонусов для завершения операции.');
        }

        match ($targetStatus) {
            OrderStatus::Delivered => $this->writeOffReservedBonuses($client, $order, $amount, $changedByUserId),
            OrderStatus::Cancelled => $this->returnReservedBonuses($client, $order, $amount, $changedByUserId),
            default => null,
        };
    }

    private function writeOffReservedBonuses(Client $client, Order $order, int $amount, ?int $changedByUserId): void
    {
        if ($client->bonus_balance < $amount) {
            throw new RuntimeException('У клиента недостаточно бонусов для окончательного списания.');
        }

        $client->update([
            'bonus_balance' => $client->bonus_balance - $amount,
            'bonus_reserved' => $client->bonus_reserved - $amount,
        ]);

        $client->bonusTransactions()->create([
            'order_id' => $order->getKey(),
            'performed_by_user_id' => $changedByUserId,
            'type' => BonusTransactionType::WriteOff,
            'amount' => $amount,
            'balance_delta' => -$amount,
            'reserved_delta' => -$amount,
            'comment' => 'Окончательное списание бонусов по доставленному заказу.',
        ]);
    }

    private function returnReservedBonuses(Client $client, Order $order, int $amount, ?int $changedByUserId): void
    {
        $client->update([
            'bonus_reserved' => $client->bonus_reserved - $amount,
        ]);

        $client->bonusTransactions()->create([
            'order_id' => $order->getKey(),
            'performed_by_user_id' => $changedByUserId,
            'type' => BonusTransactionType::ReserveReturn,
            'amount' => $amount,
            'balance_delta' => 0,
            'reserved_delta' => -$amount,
            'comment' => 'Возврат резерва по отменённому заказу.',
        ]);
    }

    private function createStatusHistory(
        Order $order,
        OrderStatus $fromStatus,
        OrderStatus $toStatus,
        ?int $changedByUserId,
        ?string $comment,
    ): void {
        $order->statusHistories()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $changedByUserId,
            'comment' => $comment,
        ]);
    }
}
