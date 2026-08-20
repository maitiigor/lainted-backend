<?php

namespace App\Domain\Social\Models;

use App\Domain\Listings\Models\Listing;
use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\GroupBuyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupBuy extends Model
{
    use HasUuid;

    protected $fillable = [
        'listing_id',
        'order_id',
        'organizer_id',
        'target_amount',
        'status',
        'deadline_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'status' => GroupBuyStatus::class,
            'deadline_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupBuyParticipant::class);
    }
}
