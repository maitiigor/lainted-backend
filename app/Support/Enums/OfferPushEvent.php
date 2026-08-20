<?php

namespace App\Support\Enums;

enum OfferPushEvent: string
{
    case Created = 'created';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
