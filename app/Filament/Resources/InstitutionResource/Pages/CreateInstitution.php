<?php

namespace App\Filament\Resources\InstitutionResource\Pages;

use App\Filament\Concerns\InteractsWithTranslationData;
use App\Filament\Resources\InstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitution extends CreateRecord
{
    use InteractsWithTranslationData;

    protected static string $resource = InstitutionResource::class;
}
