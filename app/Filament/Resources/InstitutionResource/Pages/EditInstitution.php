<?php

namespace App\Filament\Resources\InstitutionResource\Pages;

use App\Filament\Concerns\InteractsWithTranslationData;
use App\Filament\Resources\InstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstitution extends EditRecord
{
    use InteractsWithTranslationData;

    protected static string $resource = InstitutionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
