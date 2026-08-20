<?php

namespace App\Support\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case PastDue = 'past_due';
}
