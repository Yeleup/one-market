<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Concerns\HasClientHeaderActions;
use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use HasClientHeaderActions;

    protected static string $resource = ClientResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ClientResource::normalizeRecipientData($data);
    }

    protected function getHeaderActions(): array
    {
        return $this->getClientHeaderActions();
    }
}
