<?php

namespace App\Domain\Listings\Models;

use App\Support\Concerns\HasUuid;
use App\Support\Enums\ItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUuid;

    protected $fillable = [
        'parent_id',
        'item_type',
        'name',
        'slug',
        'icon_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
