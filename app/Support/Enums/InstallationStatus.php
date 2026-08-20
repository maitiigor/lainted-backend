<?php

namespace App\Support\Enums;

enum InstallationStatus: string
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
