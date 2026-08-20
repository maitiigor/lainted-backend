<?php

namespace App\Domain\Negotiation\Models;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Listings\Models\Listing;
use App\Domain\Orders\Models\Order;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\OfferParty;
use App\Support\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Offer extends Model
{
    use HasUuid;

    protected $appends = [
        'is_lowball',
    ];

    protected $fillable = [
        'listing_id',
        'conversation_id',
        'buyer_id',
        'seller_id',
        'parent_offer_id',
        'initiated_by',
        'amount',
        'round_number',
        'status',
        'expires_at',
        'responded_at',
        'agreed_at',
        'checkout_expires_at',
        'reminder_sent_6h_at',
        'reminder_sent_2h_at',
    ];

    protected function casts(): array
    {
        return [
            'initiated_by' => OfferParty::class,
            'amount' => 'decimal:2',
            'round_number' => 'integer',
            'status' => OfferStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'agreed_at' => 'datetime',
            'checkout_expires_at' => 'datetime',
            'reminder_sent_6h_at' => 'datetime',
            'reminder_sent_2h_at' => 'datetime',
        ];
    }

    /**
     * Flat 30% of listed price, same threshold regardless of condition
     * grade - the product doc never specifies a grade-conditioned
     * formula, so none is invented here.
     */
    protected function isLowball(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount < $this->listing->price * 0.30,
        );
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function parentOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'parent_offer_id');
    }

    public function counterOffers(): HasMany
    {
        return $this->hasMany(Offer::class, 'parent_offer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }
}
