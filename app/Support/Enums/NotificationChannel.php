<?php

namespace App\Support\Enums;

enum NotificationChannel: string
{
    case Push = 'push';
    case InApp = 'in_app';
}
