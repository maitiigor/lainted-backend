<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Users\Models\User;
use App\Support\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
