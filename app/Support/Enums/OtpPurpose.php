<?php

namespace App\Support\Enums;

enum OtpPurpose: string
{
    case Signup = 'signup';
    case Login = 'login';
    case PhoneChange = 'phone_change';
}
