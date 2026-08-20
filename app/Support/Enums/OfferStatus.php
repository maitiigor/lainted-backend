<?php

namespace App\Support\Enums;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Countered = 'countered';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Retracted = 'retracted';
    case CheckoutExpired = 'checkout_expired';
    case Completed = 'completed';
}
