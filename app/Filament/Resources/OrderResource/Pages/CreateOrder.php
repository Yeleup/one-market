<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Actions\Orders\CreateOrderAction;
use App\Enums\OrderSource;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

use function auth;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected ?bool $hasDatabaseTransactions = true;

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
