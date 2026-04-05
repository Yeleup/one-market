<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Concerns\HasOrderHeaderActions;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use HasOrderHeaderActions;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getOrderHeaderActions();
    }
}
