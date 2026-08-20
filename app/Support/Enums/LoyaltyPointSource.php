<?php

namespace App\Support\Enums;

enum LoyaltyPointSource: string
{
    case PurchaseReward = 'purchase_reward';
    case ReferralReward = 'referral_reward';
    case OffPlatformReportReward = 'off_platform_report_reward';
    case Redemption = 'redemption';
    case Forfeiture = 'forfeiture';
}
