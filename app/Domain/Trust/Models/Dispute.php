<?php

namespace App\Domain\Trust\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\DisputeStatus;
use App\Support\Enums\DisputeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasUuid;

    protected $fillable = [
        'order_id',
        'raised_by',
        'dispute_type',
        'buyer_statement',
        'buyer_photos',
        'seller_statement',
        'seller_photos',
        'self_resolution_offered',
        'self_resolution_accepted',
        'status',
        'admin_id',
        'admin_notes',
        'resolved_at',
        'self_resolve_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'dispute_type' => DisputeType::class,
            'buyer_photos' => 'array',
            'seller_photos' => 'array',
            'self_resolution_offered' => 'decimal:2',
            'self_resolution_accepted' => 'boolean',
            'status' => DisputeStatus::class,
            'resolved_at' => 'datetime',
            'self_resolve_expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
