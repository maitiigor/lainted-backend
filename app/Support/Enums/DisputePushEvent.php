<?php

namespace App\Support\Enums;

enum DisputePushEvent: string
{
    case Raised = 'raised';
    case SellerResponded = 'seller_responded';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
}
