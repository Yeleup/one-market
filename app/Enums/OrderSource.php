<?php

namespace App\Enums;

enum OrderSource: string
{
    case Client = 'client';
    case Admin = 'admin';
}
