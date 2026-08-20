<?php

namespace App\Domain\Listings\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentlyViewed extends Model
{
    // Eloquent's naive pluralization would guess "recently_vieweds" -
    // the migration named the table "recently_viewed" (already singular).
    protected $table = 'recently_viewed';

    const CREATED_AT = null;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'listing_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
