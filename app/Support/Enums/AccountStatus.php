<?php

namespace App\Support\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Warned = 'warned';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case PendingDeletion = 'pending_deletion';
}
