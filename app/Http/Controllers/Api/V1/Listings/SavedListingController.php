<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Models\SavedListing;
use App\Http\Controllers\Api\V1\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listings = $request->user()->savedListings()
            ->with(['listing.photos', 'listing.seller'])
            ->latest('created_at')
            ->get()
            ->pluck('listing');

        return ApiResponse::success($listings);
    }

    public function store(Request $request, Listing $listing): JsonResponse
    {
        SavedListing::firstOrCreate([
            'user_id' => $request->user()->id,
            'listing_id' => $listing->id,
        ]);

        return ApiResponse::success(null, 'Listing saved.');
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        SavedListing::where('user_id', $request->user()->id)
            ->where('listing_id', $listing->id)
            ->delete();

        return ApiResponse::success(null, 'Listing unsaved.');
    }
}
