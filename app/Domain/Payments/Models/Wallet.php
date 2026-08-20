<?php

namespace App\Domain\Payments\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'loyalty_points',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'loyalty_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function loyaltyPointTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }
}
