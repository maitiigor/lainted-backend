<?php

namespace App\Support\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Prepared = 'prepared';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
