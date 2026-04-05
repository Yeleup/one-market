<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\HasUserHeaderActions;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use HasUserHeaderActions;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getUserHeaderActions();
    }
}
