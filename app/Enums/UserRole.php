<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';

    public function getLabel(): string|Htmlable|null
    {
        return __("admin.enums.user_role.{$this->value}");
    }
}
