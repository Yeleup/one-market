<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OrderRecipientType: string implements HasLabel
{
    case Client = 'client';
    case Other = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return __("admin.enums.order_recipient_type.{$this->value}");
    }
}
