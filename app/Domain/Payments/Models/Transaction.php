<?php

namespace App\Domain\Payments\Models;

use App\Domain\Listings\Models\FeaturedSellerSlot;
use App\Domain\Listings\Models\ListingBoost;
use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\TransactionStatus;
use App\Support\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'order_id',
        'subscription_id',
        'type',
        'amount',
        'currency',
        'gateway',
        'gateway_reference',
        'status',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'gateway_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function listingBoosts(): HasMany
    {
        return $this->hasMany(ListingBoost::class);
    }

    public function featuredSellerSlots(): HasMany
    {
        return $this->hasMany(FeaturedSellerSlot::class);
    }
}
