<?php

namespace App\Domain\Orders\Models;

use App\Domain\Listings\Models\Bundle;
use App\Domain\Listings\Models\DiscountCode;
use App\Domain\Listings\Models\Listing;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Payments\Models\Escrow;
use App\Domain\Payments\Models\Transaction;
use App\Domain\Payments\Models\WalletTransaction;
use App\Domain\Social\Models\GroupBuy;
use App\Domain\Trust\Models\Dispute;
use App\Domain\Trust\Models\Review;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\DeliveryTier;
use App\Support\Enums\LogisticsProvider;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasUuid;

    // The buyer's own pickup code, shown to the seller only face-to-face
    // at handoff - never in an API response, except back to the buyer
    // themselves. Restored via makeVisible() in OrderController.
    protected $hidden = [
        'pickup_code',
    ];

    protected $fillable = [
        'listing_id',
        'bundle_id',
        'offer_id',
        'delivery_address_id',
        'discount_code_id',
        'discount_amount',
        'buyer_id',
        'seller_id',
        'delivery_tier',
        'payment_method',
        'logistics_provider',
        'tracking_reference',
        'item_price',
        'commission_rate',
        'commission_amount',
        'protection_fee',
        'delivery_fee',
        'total_amount',
        'status',
        'delivered_at',
        'dispute_deadline_at',
        'completed_at',
        'pickup_code',
        'pickup_code_entered_at',
        'package_photo_url',
        'buyer_acknowledged_at',
        'delivery_photo_url',
        'delivery_confirmed_code',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'delivery_tier' => DeliveryTier::class,
            'payment_method' => PaymentMethod::class,
            'logistics_provider' => LogisticsProvider::class,
            'item_price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'protection_fee' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => OrderStatus::class,
            'delivered_at' => 'datetime',
            'dispute_deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'pickup_code_entered_at' => 'datetime',
            'buyer_acknowledged_at' => 'datetime',
            'delivery_confirmed_code' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function installationBooking(): HasOne
    {
        return $this->hasOne(InstallationBooking::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(Escrow::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'related_order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function groupBuy(): HasOne
    {
        return $this->hasOne(GroupBuy::class);
    }
}
