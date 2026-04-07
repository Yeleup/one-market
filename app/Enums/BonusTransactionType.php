<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum BonusTransactionType: string implements HasLabel
{
    case Accrual = 'accrual';
    case Reserve = 'reserve';
    case WriteOff = 'write_off';
    case ReserveReturn = 'reserve_return';
    case ManualDebit = 'manual_debit';

    public function getLabel(): string|Htmlable|null
    {
        return __("admin.enums.bonus_transaction_type.{$this->value}");
    }
}
