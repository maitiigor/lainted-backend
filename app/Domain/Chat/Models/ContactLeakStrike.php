<?php

namespace App\Domain\Chat\Models;

use App\Domain\Trust\Models\Strike;
use App\Domain\Users\Models\User;
use App\Support\Enums\ContactLeakPattern;
use App\Support\Enums\ContactLeakSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactLeakStrike extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'strike_id',
        'source_type',
        'source_id',
        'detected_pattern',
        'matched_text',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ContactLeakSourceType::class,
            'detected_pattern' => ContactLeakPattern::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function strike(): BelongsTo
    {
        return $this->belongsTo(Strike::class);
    }
}
