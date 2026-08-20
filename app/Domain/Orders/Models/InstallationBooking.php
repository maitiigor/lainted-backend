<?php

namespace App\Domain\Orders\Models;

use App\Support\Enums\InstallationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationBooking extends Model
{
    protected $fillable = [
        'order_id',
        'scheduled_at',
        'status',
        'fee',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => InstallationStatus::class,
            'fee' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
