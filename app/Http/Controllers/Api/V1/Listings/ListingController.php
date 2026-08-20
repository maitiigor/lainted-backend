<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Models\RecentlyViewed;
use App\Domain\Listings\Services\ListingService;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Listings\StoreListingRequest;
use App\Http\Requests\Api\V1\Listings\UpdateListingRequest;
use App\Support\Enums\ListingStatus;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->listings()->with(['seller', 'photos'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Always the requester's own listings here - safe to reveal
        // minimum_price unconditionally, unlike show() below.
        return ApiResponse::success($query->get()->makeVisible('minimum_price'));
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        try {
            $listing = $this->listings->create($request->user(), $request->validated());
        } catch (ListingException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            $listing->load(['seller', 'photos'])->makeVisible('minimum_price'),
            'Listing created.',
            201,
        );
    }

    public function show(Request $request, Listing $listing): JsonResponse
    {
        $isOwner = $listing->seller_id === $request->user()->id;

        // A listing flips to Sold the moment an order is created for it
        // (see OrderService), so its actual buyer must still be able to
        // view it afterward - e.g. tapping through from their own Order
        // Detail screen, or a stale cached feed row from before they
        // bought it. Anyone else gets the same 404 a non-owner always
        // would for an inactive listing.
        $isBuyer = Order::where('listing_id', $listing->id)
            ->where('buyer_id', $request->user()->id)
            ->exists();

        if (! $isOwner && ! $isBuyer && $listing->status !== ListingStatus::Active) {
            // Don't distinguish "doesn't exist" from "exists but you can't
            // see it" - both read as a plain 404 to the caller.
            throw new NotFoundHttpException();
        }

        if (! $isOwner) {
            RecentlyViewed::updateOrCreate(
                ['user_id' => $request->user()->id, 'listing_id' => $listing->id],
                ['viewed_at' => now()],
            );

            // Plain query-builder increment, not $listing->increment() -
            // that would also touch updated_at, which the Filament admin
            // panel sorts by, so every buyer view would reshuffle it.
            DB::table('listings')->where('id', $listing->id)->increment('views_count');
        }

        $listing->load(['seller', 'photos']);

        // Doc: seller's minimum is "private - never shown to buyer."
        if ($isOwner) {
            $listing->makeVisible('minimum_price');
        }

        return ApiResponse::success($listing);
    }

    public function update(UpdateListingRequest $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        try {
            $updated = $this->listings->update($listing, $request->validated());
        } catch (ListingException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            $updated->load(['seller', 'photos'])->makeVisible('minimum_price'),
            'Listing updated.',
        );
    }

    public function publish(Request $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        try {
            $this->listings->publish($listing);
        } catch (ListingException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            $listing->refresh()->load(['seller', 'photos'])->makeVisible('minimum_price'),
            'Listing published.',
        );
    }

    public function markSold(Request $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);
        $this->listings->markSold($listing);

        return ApiResponse::success($listing->refresh(), 'Listing marked as sold.');
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);
        $this->listings->remove($listing);

        return ApiResponse::success(null, 'Listing removed.');
    }

    private function authorizeOwner(Request $request, Listing $listing): void
    {
        if ($listing->seller_id !== $request->user()->id) {
            abort(403, 'You do not own this listing.');
        }
    }
}
