<?php

namespace App\Domain\Listings\Services;

use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Category;
use App\Domain\Listings\Models\Listing;
use App\Domain\Trust\Services\UserModerationService;
use App\Domain\Users\Models\User;
use App\Support\ContactLeakDetector;
use App\Support\Enums\ConditionGrade;
use App\Support\Enums\ContactLeakSourceType;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\ListingSource;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\SellerType;
use App\Support\Enums\StrikeType;
use App\Support\Enums\WeightTier;
use Illuminate\Support\Facades\DB;

class ListingService
{
    public function __construct(private readonly UserModerationService $moderation) {}

    /**
     * Always starts as a draft - grade/photo-count enforcement only
     * happens at publish() time, not here, so a seller can save an
     * incomplete listing and finish it later.
     */
    public function create(User $seller, array $data): Listing
    {
        $category = Category::findOrFail($data['category_id']);

        // Doc's seller-type feature table: bale listings are business-only,
        // wardrobe cleanout is personal-only.
        if (! empty($data['is_bale']) && $seller->seller_type !== SellerType::Business) {
            throw new ListingException('Bale listings are only available to business sellers.');
        }

        if (! empty($data['is_wardrobe_cleanout']) && $seller->seller_type !== SellerType::Personal) {
            throw new ListingException('Wardrobe Cleanout is only available to personal sellers.');
        }

        $listing = Listing::create([
            ...$data,
            'seller_id' => $seller->id,
            // All three required, no DB default - derived/defaulted
            // server-side rather than exposed as form fields (only Fashion
            // is active for V1; listing_source just mirrors the seller's
            // own type; weight_tier only matters once Electronics/Household
            // categories go active).
            'item_type' => $category->item_type,
            'weight_tier' => WeightTier::Standard,
            'listing_source' => $seller->seller_type === SellerType::Business
                ? ListingSource::Business
                : ListingSource::Wardrobe,
            'status' => ListingStatus::Draft,
        ]);

        $this->assertNoContactLeak($seller, $listing, $listing->title, $listing->description);

        return $listing;
    }

    public function update(Listing $listing, array $data): Listing
    {
        if (array_key_exists('title', $data) || array_key_exists('description', $data)) {
            $this->assertNoContactLeak(
                $listing->seller,
                $listing,
                $data['title'] ?? $listing->title,
                $data['description'] ?? $listing->description,
            );
        }

        $listing->fill($data);

        if ($listing->status === ListingStatus::Active && $listing->isDirty('condition_grade')) {
            $this->assertPhotoCountMeetsGrade($listing, $listing->condition_grade);
        }

        $listing->save();

        return $listing;
    }

    public function publish(Listing $listing): void
    {
        $seller = $listing->seller;
        $remaining = $seller->remainingListingSlots();

        if ($remaining !== null && $remaining <= 0) {
            throw new ListingException('You have reached your active listing limit.');
        }

        $this->assertPhotoCountMeetsGrade($listing, $listing->condition_grade);

        if ($listing->is_bale && (! $listing->bale_type || ! $listing->bale_item_count || ! $listing->bale_origin_country)) {
            throw new ListingException('Bale listings need a bale type, item count, and origin country.');
        }

        $listing->update([
            'status' => ListingStatus::Active,
            'published_at' => now(),
        ]);
    }

    public function markSold(Listing $listing): void
    {
        $listing->update([
            'status' => ListingStatus::Sold,
            'sold_at' => now(),
        ]);
    }

    /**
     * Status flip only - never combined with soft-delete. `Listing` already
     * has `SoftDeletes` for the admin side; if a seller-removed listing were
     * also soft-deleted, the global scope would hide it from every normal
     * query (including the seller's own `?status=removed` filter) before
     * the status match ever had a chance to run.
     */
    public function remove(Listing $listing): void
    {
        $listing->update(['status' => ListingStatus::Removed]);
    }

    private function assertPhotoCountMeetsGrade(Listing $listing, ConditionGrade $grade): void
    {
        $photoCount = $listing->photos()->count();
        $minimum = $grade->minimumPhotoCount();

        if ($photoCount < $minimum) {
            throw new ListingException(
                "Grade {$grade->value} listings need at least {$minimum} photos - you have {$photoCount}."
            );
        }
    }

    private function assertNoContactLeak(User $seller, Listing $listing, string $title, string $description): void
    {
        $match = ContactLeakDetector::detect($title) ?? ContactLeakDetector::detect($description);

        if ($match === null) {
            return;
        }

        DB::transaction(function () use ($seller, $listing, $match) {
            $strike = $this->moderation->issueStrike(
                $seller,
                StrikeType::ContactLeak,
                'Contact info detected in listing text.',
                IssuedBy::System,
                referenceType: 'listing',
                referenceId: $listing->id,
            );

            ContactLeakStrike::create([
                'user_id' => $seller->id,
                'strike_id' => $strike->id,
                'source_type' => ContactLeakSourceType::Listing,
                'source_id' => $listing->id,
                'detected_pattern' => $match['pattern'],
                'matched_text' => $match['matched_text'],
            ]);

            // The draft was never valid - remove it outright rather than
            // leaving a half-created row with soft-delete's deleted_at set
            // (that state is reserved for admin-side removal, see remove()).
            $listing->forceDelete();
        });

        throw new ListingException(
            "Please remove contact info (phone numbers, WhatsApp, social handles, email) ".
            "from your listing - sharing these off-platform isn't allowed."
        );
    }
}
