<?php

namespace App\Support\Enums;

enum DeliveryTier: string
{
    case Pickup = 'pickup';
    case Standard = 'standard';
    case Bulky = 'bulky';
    case Furniture = 'furniture';
}
