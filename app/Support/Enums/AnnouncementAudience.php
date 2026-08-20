<?php

namespace App\Support\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Buyers = 'buyers';
    case Sellers = 'sellers';
    case BusinessSellers = 'business_sellers';
}
