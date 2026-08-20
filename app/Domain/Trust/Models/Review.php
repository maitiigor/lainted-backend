<?php

namespace App\Domain\Trust\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\ReviewerRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUuid;

    protected $fillable = [
        'order_id',
        'reviewer_id',
        'reviewee_id',
        'reviewer_role',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'reviewer_role' => ReviewerRole::class,
            'rating' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
