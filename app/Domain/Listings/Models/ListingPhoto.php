<?php

namespace App\Domain\Listings\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingPhoto extends Model
{
    protected $fillable = [
        'listing_id',
        'original_path',
        'watermarked_path',
        'position',
    ];

    // Raw disk paths aren't useful to a client - `url` (below) is the
    // full, host-prefixed link to the watermarked copy they should render.
    protected $hidden = [
        'original_path',
        'watermarked_path',
    ];

    protected $appends = [
        'url',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => request()?->getSchemeAndHttpHost().'/storage/'.$this->watermarked_path,
        );
    }
}
