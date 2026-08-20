<?php

namespace App\Domain\Users\Models;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Listings\Models\Bundle;
use App\Domain\Listings\Models\DiscountCode;
use App\Domain\Listings\Models\FeaturedSellerSlot;
use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Models\RecentlyViewed;
use App\Domain\Listings\Models\SavedListing;
use App\Domain\Locations\Models\Lga;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Notifications\Models\NotificationPreference;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\BankAccount;
use App\Domain\Payments\Models\Subscription;
use App\Domain\Payments\Models\Transaction;
use App\Domain\Payments\Models\Wallet;
use App\Domain\Social\Models\GroupBuy;
use App\Domain\Social\Models\Referral;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\Review;
use App\Domain\Trust\Models\Strike;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\AccountStatus;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\PreferredLanguage;
use App\Support\Enums\SellerRank;
use App\Support\Enums\SellerType;
use App\Support\Enums\SubscriptionStatus;
use App\Support\Enums\VerificationType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuid, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'google_id',
        'password',
        'photo_path',
        'bio',
        'lga_id',
        'landmark',
        'pickup_address_id',
        'seller_type',
        'account_status',
        'suspended_until',
        'is_verified',
        'verification_type',
        'verification_number_encrypted',
        'seller_rank',
        'preferred_language',
        'accepted_seller_agreement_at',
        'profile_completed_at',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // `lgaName()` touches this relation, which Eloquent then includes
        // in serialization as a side effect - clients get `lga_id` (raw)
        // and `lga_name` (display string) instead of the full related model.
        'lga',
    ];

    protected $appends = [
        'lga_name',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_until' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'is_verified' => 'boolean',
            'verification_number_encrypted' => 'encrypted',
            'accepted_seller_agreement_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'last_active_at' => 'datetime',
            'seller_type' => SellerType::class,
            'account_status' => AccountStatus::class,
            'verification_type' => VerificationType::class,
            'seller_rank' => SellerRank::class,
            'preferred_language' => PreferredLanguage::class,
            // 'float', not 'decimal:2' - decimal casts serialize to JSON as
            // a string ("11.50"), which crashes mobile's `User.fromJson()`
            // (`json['trust_score'] as num?` throws on a String, not just
            // returns null). These are display-only computed scores, never
            // used in further precise arithmetic, so float has no downside.
            'rating' => 'float',
            'trust_score' => 'float',
            'response_rate' => 'float',
        ];
    }

    public function canAccessPanel(): bool
    {
        return $this->hasRole('admin');
    }

    public function needsProfileSetup(): bool
    {
        return is_null($this->profile_completed_at);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    /**
     * Human-readable LGA name for API responses - `lga_id` alone isn't
     * enough for the mobile client to display anything without a second
     * round trip, and exposing the full related Lga model here (with its
     * own uuid, timestamps, etc.) is more than the client needs.
     */
    protected function lgaName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->lga?->name,
        );
    }

    /**
     * A seller's saved default pickup location for real courier bookings
     * (see SendboxService) - reuses DeliveryAddress as-is, nothing about
     * that model is structurally buyer-specific despite only ever being
     * used for buyers before now.
     */
    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'pickup_address_id');
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }

    /**
     * Inverse of blockedUsers() - who has blocked ME. Needed to check
     * "has the other party blocked me" in chat, which the forward
     * relation alone can't answer.
     */
    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'blocked_user_id', 'user_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followee_id', 'follower_id');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(Bundle::class, 'seller_id');
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class, 'seller_id');
    }

    public function featuredSellerSlots(): HasMany
    {
        return $this->hasMany(FeaturedSellerSlot::class, 'seller_id');
    }

    public function savedListings(): HasMany
    {
        return $this->hasMany(SavedListing::class);
    }

    public function recentlyViewed(): HasMany
    {
        return $this->hasMany(RecentlyViewed::class);
    }

    public function buyerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function sellerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function offersMade(): HasMany
    {
        return $this->hasMany(Offer::class, 'buyer_id');
    }

    public function offersReceived(): HasMany
    {
        return $this->hasMany(Offer::class, 'seller_id');
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();
    }

    /**
     * Null means unlimited. Personal sellers have no subscription at all -
     * a flat, doc-specified cap of 20. Business sellers read their plan's
     * `max_active_listings`, but a business seller with no active
     * subscription (reachable via direct admin edits, not just normal
     * signup) falls back to the Free plan's cap rather than being treated
     * as unlimited - those are two different kinds of null and must not
     * collapse into the same branch.
     */
    public function remainingListingSlots(): ?int
    {
        $activeCount = $this->listings()->where('status', ListingStatus::Active)->count();

        return match ($this->seller_type) {
            SellerType::None => 0,
            SellerType::Personal => max(0, 20 - $activeCount),
            SellerType::Business => $this->remainingBusinessListingSlots($activeCount),
        };
    }

    private function remainingBusinessListingSlots(int $activeCount): ?int
    {
        $subscription = $this->activeSubscription();

        if ($subscription === null) {
            return max(0, 10 - $activeCount);
        }

        $cap = $subscription->subscriptionPlan->max_active_listings;

        return $cap === null ? null : max(0, $cap - $activeCount);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(Strike::class);
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function groupBuysOrganized(): HasMany
    {
        return $this->hasMany(GroupBuy::class, 'organizer_id');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }
}
