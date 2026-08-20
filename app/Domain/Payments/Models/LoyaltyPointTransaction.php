<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\LedgerDirection;
use App\Support\Enums\LoyaltyPointSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'wallet_id',
        'type',
        'source',
        'points',
        'points_balance_after',
        'related_order_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => LedgerDirection::class,
            'source' => LoyaltyPointSource::class,
            'points' => 'integer',
            'points_balance_after' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }
}
