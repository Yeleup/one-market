<?php

namespace App\Actions\Orders;

use App\Enums\BonusTransactionType;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncOrderTotalsAction
{
    public function handle(Order $order, ?int $performedByUserId = null): Order
    {
        /** @var Order $syncedOrder */
        $syncedOrder = DB::transaction(function () use ($order, $performedByUserId): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            $totals = $this->calculateItemTotals($lockedOrder);
            $targetReservedBonusAmount = $this->shouldSynchronizeReserve($lockedOrder)
                ? $totals['total_bonus']
                : (int) $lockedOrder->reserved_bonus_amount;
            $reserveDelta = $targetReservedBonusAmount - (int) $lockedOrder->reserved_bonus_amount;

            if ($reserveDelta !== 0) {
                $this->syncClientReserve($lockedOrder, $reserveDelta, $performedByUserId);
            }

            $lockedOrder->update([
                'total_bonus' => $totals['total_bonus'],
                'total_weight_grams' => $totals['total_weight_grams'],
                'reserved_bonus_amount' => $targetReservedBonusAmount,
            ]);

            return $lockedOrder->fresh();
        });

        return $syncedOrder;
    }

    /**
     * @return array{total_bonus: int, total_weight_grams: int}
     */
    private function calculateItemTotals(Order $order): array
    {
        $totals = $order->items()
            ->selectRaw('COALESCE(SUM(line_total_bonus), 0) as total_bonus')
            ->selectRaw('COALESCE(SUM(line_total_weight_grams), 0) as total_weight_grams')
            ->first();

        return [
            'total_bonus' => (int) ($totals?->total_bonus ?? 0),
            'total_weight_grams' => (int) ($totals?->total_weight_grams ?? 0),
        ];
    }

    private function shouldSynchronizeReserve(Order $order): bool
    {
        return ! in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true);
    }

    private function syncClientReserve(Order $order, int $reserveDelta, ?int $performedByUserId): void
    {
        /** @var Client $client */
        $client = Client::query()
            ->lockForUpdate()
            ->findOrFail($order->client_id);

        if ($reserveDelta > 0) {
            $availableBonusBalance = (int) $client->bonus_balance - (int) $client->bonus_reserved;

            if ($reserveDelta > $availableBonusBalance) {
                throw new RuntimeException('Недостаточно доступных бонусов для обновления состава заказа.');
            }
        }

        $updatedReservedBalance = (int) $client->bonus_reserved + $reserveDelta;

        if ($updatedReservedBalance < 0) {
            throw new RuntimeException('Некорректный пересчёт резерва бонусов клиента.');
        }

        $client->update([
            'bonus_reserved' => $updatedReservedBalance,
        ]);

        $client->bonusTransactions()->create([
            'order_id' => $order->getKey(),
            'performed_by_user_id' => $performedByUserId,
            'type' => $reserveDelta > 0 ? BonusTransactionType::Reserve : BonusTransactionType::ReserveReturn,
            'amount' => abs($reserveDelta),
            'balance_delta' => 0,
            'reserved_delta' => $reserveDelta,
            'comment' => 'Автоматическая корректировка резерва после изменения позиций заказа.',
        ]);
    }
}
