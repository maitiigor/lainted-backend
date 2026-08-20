<?php

namespace App\Support\Enums;

enum BaleType: string
{
    case Premium = 'premium';
    case Mixed = 'mixed';
    case Economy = 'economy';
    case Sorted = 'sorted';
}
