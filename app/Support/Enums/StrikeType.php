<?php

namespace App\Support\Enums;

enum StrikeType: string
{
    case ContactLeak = 'contact_leak';
    case LowballAbuse = 'lowball_abuse';
    case GradeMismatch = 'grade_mismatch';
    case OffPlatformPayment = 'off_platform_payment';
    case Other = 'other';
}
