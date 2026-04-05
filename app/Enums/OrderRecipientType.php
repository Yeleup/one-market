<?php

namespace App\Enums;

enum OrderRecipientType: string
{
    case Client = 'client';
    case Other = 'other';
}
