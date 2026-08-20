<?php

namespace App\Support\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Offer = 'offer';
    case System = 'system';
}
