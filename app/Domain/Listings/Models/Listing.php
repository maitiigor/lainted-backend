<?php

namespace App\Domain\Listings\Models;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Locations\Models\Lga;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Orders\Models\Order;
use App\Domain\Social\Models\FlashSale;
use App\Domain\Social\Models\GroupBuy;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\BaleType;
use App\Support\Enums\ConditionGrade;
use App\Support\Enums\ItemType;
use App\Support\Enums\ListingSource;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\VerificationStatus;
use App\Support\Enums\WeightTier;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasUuid, SoftDeletes;

    protected $hidden = [
        // `categoryName()`/`lgaName()` touch these relations, which Eloquent
        // then includes in serialization as a side effect unless hidden -
        // same pattern as User::lgaName().
        'category',
        'lga',
        // Doc: seller's minimum is "private - never shown to buyer." Was
        // inert until Phase 5 started actually reading it (auto-accept)
        // - restored only for the owner, see ListingController::show().
        'minimum_price',
    ];

    protected $appends = [
        'category_name',
        'lga_name',
    ];

    protected $fillable = [
        'seller_id',
        'category_id',
        'title',
        'description',
        'item_type',
        'condition_grade',
        'fault_description',
        'size',
        'brand',
        'model',
        'serial_number_encrypted',
        'weight_tier',
        'price',
        'is_negotiable',
        'minimum_price',
        'is_functional',
        'requires_installation',
        'lga_id',
        'landmark',
        'listing_source',
        'is_bale',
        'bale_type',
        'bale_item_count',
        'bale_origin_country',
        'is_wardrobe_cleanout',
        'verification_status',
        'verification_video_path',
        'verified_at',
        'verified_by',
        'is_platform_verified',
        'status',
        'views_count',
        'published_at',
        'expires_at',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'condition_grade' => ConditionGrade::class,
            'serial_number_encrypted' => 'encrypted',
            'weight_tier' => WeightTier::class,
            'price' => 'decimal:2',
            'is_negotiable' => 'boolean',
            'minimum_price' => 'decimal:2',
            'is_functional' => 'boolean',
            'requires_installation' => 'boolean',
            'listing_source' => ListingSource::class,
            'is_bale' => 'boolean',
            'bale_type' => BaleType::class,
            'is_wardrobe_cleanout' => 'boolean',
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'is_platform_verified' => 'boolean',
            'status' => ListingStatus::class,
            'views_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    protected function categoryName(): Attribute
    {
        return Attribute::make(get: fn () => $this->category?->name);
    }

    protected function lgaName(): Attribute
    {
        return Attribute::make(get: fn () => $this->lga?->name);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position');
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'bundle_listings');
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(ListingBoost::class);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_listings')->withPivot('created_at');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function groupBuys(): HasMany
    {
        return $this->hasMany(GroupBuy::class);
    }

    public function flashSales(): HasMany
    {
        return $this->hasMany(FlashSale::class);
    }
}
