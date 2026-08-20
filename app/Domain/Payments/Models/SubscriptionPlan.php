<?php

namespace App\Domain\Payments\Models;

use App\Support\Enums\SubscriptionPlanCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'max_active_listings',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'code' => SubscriptionPlanCode::class,
            'price' => 'decimal:2',
            'max_active_listings' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
