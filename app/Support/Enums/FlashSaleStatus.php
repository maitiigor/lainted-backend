<?php

namespace App\Support\Enums;

enum FlashSaleStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
}
