<?php

namespace App\Domain\Listings\Models;

use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bundle extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'title',
        'required_item_count',
        'bundle_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_item_count' => 'integer',
            'bundle_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'bundle_listings');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Domain\Orders\Models\Order::class);
    }
}
