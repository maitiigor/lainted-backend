<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Http\Controllers\Api\V1\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentlyViewedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listings = $request->user()->recentlyViewed()
            ->with(['listing.photos', 'listing.seller'])
            ->orderByDesc('viewed_at')
            ->limit(50)
            ->get()
            ->pluck('listing');

        return ApiResponse::success($listings);
    }
}
