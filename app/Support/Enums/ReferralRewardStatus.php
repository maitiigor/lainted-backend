<?php

namespace App\Support\Enums;

enum ReferralRewardStatus: string
{
    case Pending = 'pending';
    case Credited = 'credited';
    case Forfeited = 'forfeited';
}
