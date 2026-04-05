<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Concerns\InteractsWithTranslationData;
use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use InteractsWithTranslationData;

    protected static string $resource = ProductResource::class;
}
