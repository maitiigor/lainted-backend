<?php

namespace App\Domain\Payments\Models;

use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasUuid;

    // The full account number is never sent back to the client - a
    // masked last4 (below) is all any saved-bank-account UI needs, and
    // the recipient_code is a pure Paystack-side implementation detail.
    protected $hidden = [
        'account_number_encrypted',
        'paystack_recipient_code',
    ];

    protected $appends = [
        'account_number_last4',
    ];

    protected $fillable = [
        'user_id',
        'bank_name',
        'bank_code',
        'account_number_encrypted',
        'account_name',
        'paystack_recipient_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'account_number_encrypted' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    protected function accountNumberLast4(): Attribute
    {
        return Attribute::get(fn () => substr((string) $this->account_number_encrypted, -4));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
