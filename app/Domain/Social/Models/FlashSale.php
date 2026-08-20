<?php

namespace App\Domain\Social\Models;

use App\Domain\Listings\Models\Listing;
use App\Domain\Users\Models\User;
use App\Support\Enums\FlashSaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSale extends Model
{
    protected $fillable = [
        'seller_id',
        'listing_id',
        'discount_percent',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => FlashSaleStatus::class,
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
