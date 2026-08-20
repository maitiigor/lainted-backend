<?php

namespace App\Support\Enums;

enum TransactionType: string
{
    case Payment = 'payment';
    case Withdrawal = 'withdrawal';
    case Refund = 'refund';
    case SubscriptionCharge = 'subscription_charge';
    case BoostPayment = 'boost_payment';
}
