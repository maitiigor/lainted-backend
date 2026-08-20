<?php

namespace App\Support\Enums;

enum ContactLeakPattern: string
{
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case Telegram = 'telegram';
    case Instagram = 'instagram';
    case Email = 'email';
}
