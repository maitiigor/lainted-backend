<?php

namespace App\Domain\Social\Models;

use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\ReferralRewardStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasUuid;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'reward_amount',
        'reward_status',
        'credited_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'reward_status' => ReferralRewardStatus::class,
            'credited_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
