<?php

namespace App\Filament\Resources\BonusTransactionResource\Pages;

use App\Filament\Resources\BonusTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBonusTransaction extends EditRecord
{
    protected static string $resource = BonusTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
