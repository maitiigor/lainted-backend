<?php

namespace App\Support\Enums;

enum OfferParty: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';

    public function opposite(): self
    {
        return $this === self::Buyer ? self::Seller : self::Buyer;
    }
}
