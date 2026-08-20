<?php

namespace App\Support\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
