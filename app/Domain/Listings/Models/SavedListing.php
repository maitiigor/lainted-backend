<?php

namespace App\Domain\Listings\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedListing extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'listing_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
