<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum OrderStatus: string implements HasLabel
{
    case New = 'new';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function getLabel(): string|Htmlable|null
    {
        return __("admin.enums.order_status.{$this->value}");
    }
}
