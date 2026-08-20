<?php

namespace App\Support\Enums;

enum LogisticsProvider: string
{
    case SelfPickup = 'self_pickup';
    case Gig = 'gig';
    case Kwik = 'kwik';
    case Sendbox = 'sendbox';
    case Other = 'other';
}
