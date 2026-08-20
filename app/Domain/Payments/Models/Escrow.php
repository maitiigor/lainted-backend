<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Support\Enums\EscrowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escrow extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'status',
        'held_at',
        'released_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => EscrowStatus::class,
            'held_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
