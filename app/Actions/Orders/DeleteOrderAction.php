<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteOrderAction
{
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            $this->assertCanDelete($lockedOrder);

            $lockedOrder->bonusTransactions()->delete();
            $lockedOrder->delete();
        });
    }

    private function assertCanDelete(Order $order): void
    {
        if ($order->status !== OrderStatus::Cancelled) {
            throw new RuntimeException('Удаление доступно только для отменённого заказа.');
        }
    }
}
