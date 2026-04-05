<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case ReadyForDelivery = 'ready_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
