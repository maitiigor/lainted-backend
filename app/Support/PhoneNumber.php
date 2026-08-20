<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a Nigerian phone number to E.164 (+234XXXXXXXXXX).
     * Returns null if the input can't be confidently normalized.
     */
    public static function normalize(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input);

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+234'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '+234'.$digits;
        }

        return null;
    }
}
