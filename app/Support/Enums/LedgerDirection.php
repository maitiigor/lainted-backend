<?php

namespace App\Support\Enums;

enum LedgerDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
