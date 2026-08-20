<?php

namespace App\Domain\Users\Models;

use App\Support\Concerns\HasUuid;
use App\Support\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasUuid;

    protected $fillable = [
        'phone',
        'code',
        'purpose',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
