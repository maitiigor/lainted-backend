<?php

namespace App\Domain\Orders\Models;

use App\Domain\Locations\Models\Lga;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAddress extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'lga_id',
        'landmark',
        'full_address_encrypted',
        'recipient_phone_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'full_address_encrypted' => 'encrypted',
            'recipient_phone_encrypted' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
