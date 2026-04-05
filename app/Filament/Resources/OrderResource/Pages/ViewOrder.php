<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Concerns\HasOrderHeaderActions;
use App\Filament\Resources\OrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use HasOrderHeaderActions;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...$this->getOrderHeaderActions(),
        ];
    }
}
