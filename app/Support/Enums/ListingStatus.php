<?php

namespace App\Support\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Sold = 'sold';
    case Expired = 'expired';
    case Removed = 'removed';
}
