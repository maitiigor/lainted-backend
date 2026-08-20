<?php

namespace App\Support\Enums;

enum IssuedBy: string
{
    case System = 'system';
    case Admin = 'admin';
}
