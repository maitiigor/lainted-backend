<?php

namespace App\Support\Enums;

enum GroupBuyStatus: string
{
    case Open = 'open';
    case Funded = 'funded';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
