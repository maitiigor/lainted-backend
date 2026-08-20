<?php

namespace App\Domain\Social\Models;

use App\Domain\Users\Models\User;
use App\Support\Enums\GroupBuyPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupBuyParticipant extends Model
{
    protected $fillable = [
        'group_buy_id',
        'user_id',
        'share_amount',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'share_amount' => 'decimal:2',
            'payment_status' => GroupBuyPaymentStatus::class,
        ];
    }

    public function groupBuy(): BelongsTo
    {
        return $this->belongsTo(GroupBuy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
