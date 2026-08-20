<?php

namespace App\Support\Enums;

enum WeightTier: string
{
    case Standard = 'standard';
    case Bulky = 'bulky';
    case Furniture = 'furniture';
}
