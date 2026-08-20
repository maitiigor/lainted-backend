<?php

namespace App\Support\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case SelfResolving = 'self_resolving';
    case AdminReview = 'admin_review';
    case ResolvedRefund = 'resolved_refund';
    case ResolvedRelease = 'resolved_release';
    case ResolvedPartial = 'resolved_partial';
}
