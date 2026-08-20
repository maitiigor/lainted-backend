<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\AnnouncementAudience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    use HasUuid;

    protected $fillable = [
        'title',
        'body',
        'audience',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
