<?php

namespace App\Support\Enums;

enum OrderPushEvent: string
{
    case PaymentConfirmed = 'payment_confirmed';
    case Prepared = 'prepared';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Completed = 'completed';
}
