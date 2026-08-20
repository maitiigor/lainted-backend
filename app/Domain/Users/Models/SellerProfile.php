<?php

namespace App\Domain\Users\Models;

use App\Support\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerProfile extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'store_description',
        'banner_path',
        'is_storefront_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_storefront_featured' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
