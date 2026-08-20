<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Models\ListingPhoto;
use App\Domain\Listings\Services\ListingPhotoService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Listings\UploadListingPhotoRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingPhotoController extends Controller
{
    public function __construct(private readonly ListingPhotoService $photos) {}

    public function store(UploadListingPhotoRequest $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        try {
            $photo = $this->photos->upload($listing, $request->file('photo'));
        } catch (ListingException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($photo, 'Photo uploaded.', 201);
    }

    public function destroy(Request $request, Listing $listing, ListingPhoto $photo): JsonResponse
    {
        $this->authorizeOwner($request, $listing);

        if ($photo->listing_id !== $listing->id) {
            abort(404);
        }

        $this->photos->delete($photo);

        return ApiResponse::success(null, 'Photo removed.');
    }

    private function authorizeOwner(Request $request, Listing $listing): void
    {
        if ($listing->seller_id !== $request->user()->id) {
            abort(403, 'You do not own this listing.');
        }
    }
}
