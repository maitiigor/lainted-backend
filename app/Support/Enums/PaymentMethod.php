<?php

namespace App\Support\Enums;

enum PaymentMethod: string
{
    case Online = 'online';
    case CashOnDelivery = 'cash_on_delivery';
}
