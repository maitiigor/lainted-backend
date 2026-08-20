<?php

namespace App\Domain\Listings\Models;

use App\Domain\Payments\Models\Transaction;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\BoostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSellerSlot extends Model
{
    use HasUuid;

    protected $fillable = [
        'seller_id',
        'transaction_id',
        'amount_paid',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => BoostStatus::class,
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
