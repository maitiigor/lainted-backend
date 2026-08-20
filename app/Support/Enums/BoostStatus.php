<?php

namespace App\Support\Enums;

enum BoostStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
