<?php

namespace App\Support\Enums;

enum SellerRank: string
{
    case New = 'new';
    case Rising = 'rising';
    case Trusted = 'trusted';
    case Top = 'top';
    case Elite = 'elite';
}
