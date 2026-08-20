<?php

namespace App\Support\Enums;

enum ContactLeakSourceType: string
{
    case Message = 'message';
    case Listing = 'listing';
}
