<?php

namespace App\Support\Enums;

enum GroupBuyPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
}
