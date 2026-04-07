<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OrderSource: string implements HasLabel
{
    case Client = 'client';
    case Admin = 'admin';

    public function getLabel(): string|Htmlable|null
    {
        return __("admin.enums.order_source.{$this->value}");
    }
}
