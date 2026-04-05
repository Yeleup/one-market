<?php

namespace App\Enums;

enum BonusTransactionType: string
{
    case Accrual = 'accrual';
    case Reserve = 'reserve';
    case WriteOff = 'write_off';
    case ReserveReturn = 'reserve_return';
    case ManualDebit = 'manual_debit';
}
