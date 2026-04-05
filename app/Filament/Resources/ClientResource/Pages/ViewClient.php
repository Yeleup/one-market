<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Concerns\HasClientHeaderActions;
use App\Filament\Resources\ClientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    use HasClientHeaderActions;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...$this->getClientHeaderActions(),
        ];
    }
}
