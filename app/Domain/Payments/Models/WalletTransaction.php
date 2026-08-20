<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\LedgerDirection;
use App\Support\Enums\WalletTransactionSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'wallet_id',
        'bank_account_id',
        'type',
        'source',
        'amount',
        'balance_after',
        'reference',
        'related_order_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => LedgerDirection::class,
            'source' => WalletTransactionSource::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function relatedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }
}
