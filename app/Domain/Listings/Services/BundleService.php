<?php

namespace App\Domain\Listings\Services;

use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Bundle;
use App\Domain\Listings\Models\Listing;
use App\Domain\Users\Models\User;
use App\Support\Enums\ListingStatus;

class BundleService
{
    /**
     * The item pool is fixed at creation, not a mutable pivot - changing
     * which listings are in a bundle means delete-and-recreate. Simpler to
     * build and reason about than full pivot-membership CRUD, and matches
     * this project's bias against building more than the doc actually asks
     * for ("buy any X items for ₦Y from same seller" doesn't need a mutable
     * pool).
     */
    public function create(User $seller, array $data): Bundle
    {
        // The client only ever knows a listing by its uuid (the same
        // external-identifier convention as every other listing-referencing
        // endpoint) - resolve to raw ids here, not at the request-validation
        // boundary, since attach() below needs the raw ids either way.
        $listings = Listing::whereIn('uuid', $data['listing_ids'])->get();

        if ($listings->count() !== count($data['listing_ids'])) {
            throw new ListingException('One or more selected listings could not be found.');
        }

        if ($listings->contains(fn (Listing $l) => $l->seller_id !== $seller->id)) {
            throw new ListingException('You can only bundle your own listings.');
        }

        if ($listings->contains(fn (Listing $l) => $l->status !== ListingStatus::Active)) {
            throw new ListingException('Only active listings can be added to a bundle.');
        }

        if ($listings->count() < 2) {
            throw new ListingException('A bundle needs at least 2 listings.');
        }

        if ($data['required_item_count'] < 2 || $data['required_item_count'] > $listings->count()) {
            throw new ListingException(
                'The required item count must be between 2 and the number of listings in the bundle.'
            );
        }

        $bundle = Bundle::create([
            'seller_id' => $seller->id,
            'title' => $data['title'],
            'required_item_count' => $data['required_item_count'],
            'bundle_price' => $data['bundle_price'],
        ]);

        $bundle->listings()->attach($listings->pluck('id'));

        return $bundle;
    }
}
