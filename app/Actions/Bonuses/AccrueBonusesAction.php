<?php

namespace App\Actions\Bonuses;

use App\Enums\BonusTransactionType;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccrueBonusesAction
{
    public function handle(Client $client, int $amount, ?int $performedByUserId = null, ?string $comment = null): Client
    {
        $this->assertPositiveAmount($amount);

        /** @var Client $updatedClient */
        $updatedClient = DB::transaction(function () use ($amount, $client, $performedByUserId, $comment): Client {
            $lockedClient = $this->lockClient($client);

            $lockedClient->update([
                'bonus_balance' => $lockedClient->bonus_balance + $amount,
            ]);

            $lockedClient->bonusTransactions()->create(
                $this->createTransactionPayload($amount, $performedByUserId, $comment),
            );

            return $lockedClient->fresh();
        });

        return $updatedClient;
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Количество бонусов должно быть больше нуля.');
        }
    }

    private function lockClient(Client $client): Client
    {
        /** @var Client $lockedClient */
        $lockedClient = Client::query()
            ->lockForUpdate()
            ->findOrFail($client->getKey());

        return $lockedClient;
    }

    /**
     * @return array<string, int|string|null|BonusTransactionType>
     */
    private function createTransactionPayload(int $amount, ?int $performedByUserId, ?string $comment): array
    {
        return [
            'order_id' => null,
            'performed_by_user_id' => $performedByUserId,
            'type' => BonusTransactionType::Accrual,
            'amount' => $amount,
            'balance_delta' => $amount,
            'reserved_delta' => 0,
            'comment' => filled($comment) ? $comment : 'Начисление из административной панели.',
        ];
    }
}
