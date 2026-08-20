<?php

namespace App\Support\Enums;

enum VerificationStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
