<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Actions\Orders\CreateOrderAction;
use App\Enums\OrderSource;
use App\Enums\RecipientType;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

use function auth;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;

    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawRecipientType = $data['recipient_type'] ?? RecipientType::Client;
        $recipientType = $rawRecipientType instanceof RecipientType
            ? $rawRecipientType
            : RecipientType::tryFrom((string) $rawRecipientType);

        if (! $recipientType) {
            throw ValidationException::withMessages([
                'data.recipient_type' => 'Некорректный тип получателя заказа.',
            ]);
        }

        $data['recipient_type'] = $recipientType;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateOrderAction::class)->handle($data, OrderSource::Admin, auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
