<?php

namespace App\Support\Enums;

enum WalletTransactionSource: string
{
    case SaleEarning = 'sale_earning';
    case Withdrawal = 'withdrawal';
    case Deposit = 'deposit';
    case Refund = 'refund';
    case LoyaltyRedemption = 'loyalty_redemption';
    case ReferralReward = 'referral_reward';
    case Forfeiture = 'forfeiture';
}
