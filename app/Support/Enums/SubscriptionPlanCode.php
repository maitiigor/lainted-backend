<?php

namespace App\Support\Enums;

enum SubscriptionPlanCode: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Elite = 'elite';
}
