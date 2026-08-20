<?php

namespace App\Domain\Listings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleListing extends Model
{
    protected $fillable = [
        'bundle_id',
        'listing_id',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
